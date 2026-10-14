@extends('mail.layout')

@section('title', 'Votre demande a bien été reçue')

@section('content')
    <p style="margin:0 0 12px;">Bonjour {{ $name }},</p>
    <p style="margin:0 0 12px;">
        Votre demande « {{ $subject }} » a bien été prise en compte. Utilisez le lien
        ci-dessous pour la suivre et y répondre — il vous donne aussi accès à l'historique
        de vos autres demandes, sans qu'un compte soit nécessaire.
    </p>
    <p style="margin:0;">Conservez ce lien : c'est le seul moyen d'accéder à votre suivi.</p>
@endsection

@section('action', 'Suivre ma demande')

@section('footnote', "Ce lien vous est personnel. Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.")
