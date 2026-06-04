<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Role;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const string VIEW = 'USER_VIEW';
    public const string CREATE = 'USER_CREATE';
    public const string EDIT = 'USER_EDIT';
    public const string DELETE = 'USER_DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::DELETE])
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        if (in_array(Role::ROLE_ROOT, $currentUser->getRoles(), true)) {
            return true;
        }

        return match ($attribute) {
            self::VIEW, self::EDIT => $currentUser->getId() === $subject->getId(),
            self::CREATE, self::DELETE => false,
        };
    }
}
