@extends('layouts.app')
@section('title',__('ui.history'))
@section('content')<x-page-heading :title="__('ui.history')"/><x-attempt-table :attempts="$attempts"/><div class="pagination">{{ $attempts->links() }}</div>@endsection
