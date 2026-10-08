@extends('mail.layout')

@section('title', 'Invitation à rejoindre '.$workspace_name)

@section('content')
    <p style="margin:0 0 12px;">Bonjour,</p>
    <p style="margin:0;">
        {{ $inviter_name }} vous invite à rejoindre l'espace de travail
        <strong>{{ $workspace_name }}</strong> sur VOXO, avec le rôle {{ $role_label }}.
    </p>
@endsection

@section('action', "Accepter l'invitation")

@section('footnote', "Cette invitation vous est personnelle. Si vous ne connaissez pas l'expéditeur, ignorez ce message.")
