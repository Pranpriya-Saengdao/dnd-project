@extends('admin.layout')
@section('admin')
<form method="POST" action="{{ route('admin.items.store') }}" class="row" style="margin-bottom:12px;flex-wrap:nowrap">
    @csrf
    <input name="item_name" placeholder="Item name" required>
    <select name="item_type"><option>Consumable</option><option>Buff</option></select>
    <input name="description" placeholder="Description">
    <input name="effect_value" type="number" min="0" placeholder="Effect" required style="width:90px">
    <button class="btn sm go">Add item</button>
</form>
<p style="font-size:13px;margin:0 0 8px">Consumable = restores HP. Buff = adds ATK for the current turn.</p>
<table>
    <tr><th>Name</th><th>Type</th><th>Description</th><th>Effect</th><th></th><th></th></tr>
    @foreach($items as $i)
        <tr>
            <form method="POST" action="{{ route('admin.items.update', $i->item_id) }}">@csrf @method('PUT')
                <td><input name="item_name" value="{{ $i->item_name }}"></td>
                <td><select name="item_type"><option @selected($i->item_type === 'Consumable')>Consumable</option><option @selected($i->item_type === 'Buff')>Buff</option></select></td>
                <td><input name="description" value="{{ $i->description }}"></td>
                <td><input name="effect_value" type="number" min="0" value="{{ $i->effect_value }}" style="width:80px"></td>
                <td><button class="btn sm go">Save</button></td>
            </form>
            <td><form method="POST" action="{{ route('admin.items.delete', $i->item_id) }}" onsubmit="return confirm('Delete item?')">@csrf @method('DELETE')<button class="btn sm danger">Delete</button></form></td>
        </tr>
    @endforeach
</table>
@endsection
