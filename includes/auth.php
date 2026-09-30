<?php

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('auth/login.php');
    }
}

function getUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function getUserRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

function getUserName(): ?string
{
    return $_SESSION['name'] ?? null;
}

function hasRole(string $role): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? null) === $role;
}

function requireRole(string $role): void
{
    requireLogin();

    if (getUserRole() !== $role) {
        redirect('index.php');
    }
}

function isFarmerApproved(): bool
{
    return hasRole('farmer')
        && ($_SESSION['approval_status'] ?? null) === 'approved';
}

function requireApprovedFarmer(): void
{
    requireRole('farmer');

    if (($_SESSION['approval_status'] ?? null) !== 'approved') {
        if (($_SESSION['approval_status'] ?? null) === 'pending') {
            redirect('farmer/pending.php');
        }

        if (($_SESSION['approval_status'] ?? null) === 'rejected') {
            redirect('farmer/rejected.php');
        }

// Unexpected approval status
redirect('index.php');
    }
}