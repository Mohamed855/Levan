<table class="form-field {{ $class ?? '' }}" @isset($height) style="height:{{ $height }}pt" @endisset>
    <tbody><tr>
        <th @isset($labelWidth) style="width:{{ $labelWidth }}pt" @endisset>{{ $label }}@if(!empty($note))<small>{{ $note }}</small>@endif</th>
        <td><span class="data-value" @if(!empty($ltr)) dir="ltr" @endif>{{ $content ?? '' }}</span></td>
    </tr></tbody>
</table>
