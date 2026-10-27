<?php

namespace App\Http\Controllers\Api\NearPay;

use App\Http\Controllers\Controller;
use App\Http\Requests\NearPay\AssignNearPayTerminalRequest;
use App\Http\Requests\NearPay\CreateNearPayTerminalRequest;
use App\Models\NearPayTerminal;
use App\Models\User;
use App\Services\Payment\NearPayAccountsService;
use App\Services\Payment\NearPayTerminalSyncService;

/**
 * Full cycle for NearPay terminal management, backed by NearPay's own Accounts API — create, assign, and
 * unassign all call NearPay first, and only write to the local database once NearPay confirms success. A
 * terminal's assignment is also surfaced on UserResource (see VerifyPinCodeRequest's response), so a
 * logged-in tech's app always knows which terminal is theirs.
 */
class NearPayTerminalController extends Controller
{
    /**
     * Calls NearPay's Create Terminal first; the local row (terminal_id = NearPay's returned terminal UUID,
     * tid = NearPay's returned hardware-style identifier) is only created once that call succeeds. Starts
     * unassigned — use assign() to hand it to a tech.
     */
    public function store(CreateNearPayTerminalRequest $request, NearPayAccountsService $nearPay)
    {
        try {
            $remote = $nearPay->createTerminal($request->trsm_code, $request->name);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Could not create the terminal with NearPay: ' . $e->getMessage(),
            ], 502);
        }

        $terminal = NearPayTerminal::create([
            'terminal_id' => $remote['id'],
            'tid'         => $remote['tid'],
            'trsm_code'   => $request->trsm_code,
            'name'        => $remote['name'] ?? $request->name,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Terminal created.',
            'data'    => $terminal,
        ], 201);
    }

    /**
     * Filters operate on the LOCAL table — accurate as of the last sync() call, not necessarily this exact
     * second. ?assigned=true|false, ?connected=true|false; both accept the same '0'/'1'/'true'/'false'
     * forms query strings naturally send.
     */
    public function index(\Illuminate\Http\Request $request)
    {
        $query = NearPayTerminal::with('assignedUser:id,username,tech_id,phone')->latest();

        if ($request->has('assigned')) {
            $request->boolean('assigned')
                ? $query->whereNotNull('assigned_user_id')
                : $query->whereNull('assigned_user_id');
        }

        if ($request->has('connected')) {
            $query->where('is_connected', $request->boolean('connected'));
        }

        $terminals = $query->get();

        return response()->json([
            'status' => true,
            'data'   => $terminals,
        ]);
    }

    /**
     * Local checks (terminal already assigned / user already holds a different one) run first, as a fast,
     * friendly first line that avoids a pointless round trip to NearPay for an obviously-invalid request —
     * the database's own unique constraint on assigned_user_id is still what actually guarantees the
     * invariant under a race, not these checks alone. Once those pass, NearPay's own Assign Terminal is
     * called; the local assignment is only written once NearPay confirms it (its own failure, e.g.
     * "terminal already assigned to another user" server-side, is surfaced as-is rather than guessed at).
     */
    public function assign(AssignNearPayTerminalRequest $request, NearPayTerminal $terminal, NearPayAccountsService $nearPay)
    {
        if ($terminal->isAssigned()) {
            return response()->json([
                'status'  => false,
                'message' => 'This terminal is already assigned to another user. Unassign it first.',
            ], 422);
        }

        $user = User::findOrFail($request->user_id);

        $existing = NearPayTerminal::where('assigned_user_id', $user->id)->first();

        if ($existing) {
            return response()->json([
                'status'  => false,
                'message' => 'This user already has a terminal assigned (' . $existing->terminal_id . '). Unassign it first.',
            ], 422);
        }

        try {
            $nearPay->assignTerminal($terminal->terminal_id, (string) $user->username, (string) $user->email, (string) $user->phone);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Could not assign the terminal with NearPay: ' . $e->getMessage(),
            ], 502);
        }

        $terminal->update([
            'assigned_user_id' => $user->id,
            'assigned_at'      => now(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Terminal assigned.',
            'data'    => $terminal->fresh('assignedUser'),
        ]);
    }

    public function unassign(NearPayTerminal $terminal, NearPayAccountsService $nearPay)
    {
        if (! $terminal->isAssigned()) {
            return response()->json([
                'status'  => false,
                'message' => 'This terminal is not currently assigned to anyone.',
            ], 422);
        }

        try {
            $nearPay->unassignTerminal($terminal->terminal_id);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Could not unassign the terminal with NearPay: ' . $e->getMessage(),
            ], 502);
        }

        $terminal->update([
            'assigned_user_id' => null,
            'assigned_at'      => null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Terminal unassigned.',
            'data'    => $terminal->fresh(),
        ]);
    }

    /**
     * Pulls every terminal from NearPay's GET /terminals and reconciles it into the local table — picks up
     * terminals/assignments made directly through NearPay's own dashboard, not only through this app. See
     * NearPayTerminalSyncService's own docblock for exactly how a remote assignment gets linked (or not) to
     * a local user.
     */
    public function sync(NearPayTerminalSyncService $sync)
    {
        try {
            $result = $sync->syncAll();
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Could not sync terminals with NearPay: ' . $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'status'  => true,
            'message' => "Synced {$result['synced']} terminal(s), {$result['linked']} linked to a local user.",
            'data'    => NearPayTerminal::with('assignedUser:id,username,tech_id,phone')->latest()->get(),
        ]);
    }

    /**
     * Technicians (type = 'tech') with no row in nearpay_terminals at all — not "assigned_user_id is
     * null for them", since that's every tech by default; this is specifically "has no terminal relationship
     * whatsoever", via whereDoesntHave against the same hasOne used for UserResource's own terminal field.
     */
    public function unassignedTechnicians()
    {
        $technicians = User::where('type', 'tech')
            ->whereDoesntHave('nearPayTerminal')
            ->get(['id', 'username', 'tech_id', 'phone', 'email']);

        return response()->json([
            'status' => true,
            'data'   => $technicians,
        ]);
    }
}
