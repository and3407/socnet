<?php

namespace App\Domain\Token\Usecases;

use App\Domain\Token\Models\Token;
use App\Domain\Token\Repositories\TokenRepository;

class CreateToken
{
    protected TokenRepository $tokenRepository;
    public function __construct()
    {
        $this->tokenRepository = new TokenRepository();
    }

    public static function createUsecase(): CreateToken
    {
        return new self();
    }

    public function createToken(int $userId, int $expireYears = 1): Token
    {
        $token = $this->tokenRepository->getTokenByUserId($userId);

        if ($token) {
            $this->tokenRepository->deleteTokenById($token->id);
        }

        $token = bin2hex(random_bytes(32));

        $currentDate = new \DateTime();
        $createdAt = $currentDate->format('Y-m-d H:i:s');

        $expiredAt = (clone $currentDate)
            ->modify("+{$expireYears} year")
            ->format('Y-m-d H:i:s');

        $this->tokenRepository->createToken($userId, $token, $createdAt, $expiredAt);

        return $this->tokenRepository->getTokenByUserId($userId);
    }
}