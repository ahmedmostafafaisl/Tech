<?php

namespace App\Http\Resources\User;

use App\Models\NearPayTerminal;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'image' => $this->image,
            'status' => $this->status,
            'role' => $this->getRoleNames()->first(), // Assuming a single role per user
            'tech_id' => $this->tech_id,
            'warehouse_id' => $this->warehouse_id,
            'personnel_number' => $this->personnel_number,
            'fcm_token' => $this->fcm_token,
            // Accessing the relationship directly lazy loads it if it wasn't eager loaded — fine here since
            // this resource always serialises one user at a time (verify-pin, etc.), never a list, so
            // there's no N+1 risk to guard against. When this tech has no terminal of their own AND
            // default_terminal_active is on, falls back to the one terminal marked is_default (see
            // NearPayTerminalController::setDefault()) rather than null, so a tech with nothing assigned
            // can still take NFC payments using the shared default. is_default is included either way, so
            // the client can tell "my own terminal" apart from "the shared fallback".
            'nearpay_terminal' => $this->resolveNearPayTerminal(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    private function resolveNearPayTerminal(): ?array
    {
        $terminal = $this->nearPayTerminal;

        if (! $terminal && Setting::isActive('default_terminal_active', false)) {
            $terminal = NearPayTerminal::where('is_default', true)->first();
        }

        if (! $terminal) {
            return null;
        }

        return [
            'id' => $terminal->id,
            'terminal_id' => $terminal->terminal_id,
            'name' => $terminal->name,
            'assigned_at' => optional($terminal->assigned_at)->format('Y-m-d H:i:s'),
            'is_default' => (bool) $terminal->is_default,
        ];
    }
}
