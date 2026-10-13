@extends('mail.layout')

@section('title', $threshold_percent >= 100 ? 'Quota atteint' : 'Vous approchez de votre quota')

@section('content')
    <p style="margin:0 0 12px;">Bonjour,</p>
    <p style="margin:0;">
        @if ($threshold_percent >= 100)
            L'espace de travail <strong>{{ $workspace_name }}</strong> a atteint son quota de
            {{ $quota_label }} sur VOXO.
        @else
            L'espace de travail <strong>{{ $workspace_name }}</strong> a dépassé {{ $threshold_percent }} %
            de son quota de {{ $quota_label }} sur VOXO.
        @endif
    </p>
@endsection

@section('action', 'Consulter la facturation')

@section('footnote', "Vous recevez cet e-mail car vous administrez cet espace de travail.")
