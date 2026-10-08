@extends('mail.layout')

@section('title', 'Confirmez votre adresse e-mail')

@section('content')
    <p style="margin:0 0 12px;">Bonjour {{ $name }},</p>
    <p style="margin:0;">Confirmez votre adresse e-mail pour terminer la création de votre compte VOXO.</p>
@endsection

@section('action', 'Confirmer mon adresse')

@section('footnote', "Vous n'avez pas créé de compte ? Ignorez ce message, rien ne sera activé.")
