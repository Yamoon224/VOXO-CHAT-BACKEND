@extends('mail.layout')

@section('title', 'Réinitialisation de votre mot de passe')

@section('content')
    <p style="margin:0 0 12px;">Bonjour {{ $name }},</p>
    <p style="margin:0;">Vous avez demandé à choisir un nouveau mot de passe. Ce lien est valable une heure.</p>
@endsection

@section('action', 'Choisir un nouveau mot de passe')

@section('footnote', "Vous n'êtes pas à l'origine de cette demande ? Ignorez ce message, votre mot de passe reste inchangé.")
