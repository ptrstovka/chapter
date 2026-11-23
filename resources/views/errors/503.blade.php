@extends('errors::minimal')

@section('title', __('maintenance_page_message'))
@section('code', '503')
@section('message')
    {{ __('maintenance_page_message') }}
@endsection
