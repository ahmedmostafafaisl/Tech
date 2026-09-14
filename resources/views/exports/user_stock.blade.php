<table>
    <thead>
        <tr>
            <th>User ID</th>
            <th>User Tech ID</th>
            <th>User Name</th>
            <th>Item Type</th>
            <th>Item Number</th>
            <th>Quantity</th>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $user)
            @if($user->stock)
                {{-- Items --}}
                @foreach($user->stock->items as $item)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->tech_id ?? $user->technician_rec_id }}</td>
                        <td>{{ $user->name ?? '-' }}</td>
                        <td>Item</td>
                        <td>{{ $item->item_number }}</td>
                        <td>{{ $item->quantity }}</td>
                    </tr>
                @endforeach

                {{-- Parts --}}
                @foreach($user->stock->parts as $part)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->tech_id ?? $user->technician_rec_id }}</td>
                        <td>{{ $user->username ?? '-' }}</td>
                        <td>Part</td>
                        <td>{{ $part->item_number }}</td>
                        <td>{{ $part->quantity }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->tech_id ?? $user->technician_rec_id }}</td>
                    <td>{{ $user->name ?? '-' }}</td>
                    <td colspan="3" style="color: red;">No stock found</td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
