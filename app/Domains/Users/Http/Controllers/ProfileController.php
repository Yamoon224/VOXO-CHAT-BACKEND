<?php

namespace App\Domains\Users\Http\Controllers;

use App\Domains\Users\Http\Requests\UpdatePasswordRequest;
use App\Domains\Users\Http\Requests\UpdateProfileRequest;
use App\Domains\Users\Http\Resources\UserResource;
use App\Domains\Users\Services\ProfileService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profile) {}

    public function update(UpdateProfileRequest $request): UserResource
    {
        return new UserResource($this->profile->update($request->user(), $request->profileAttributes()));
    }

    public function updatePassword(UpdatePasswordRequest $request): Response
    {
        $this->profile->changePassword(
            $request->user(),
            $request->string('current_password')->toString(),
            $request->string('password')->toString(),
        );

        return response()->noContent();
    }
}
