<?php

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
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
        header('Location: ' . BASE_URL . 'index.php');
        exit;
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
            header('Location: ' . BASE_URL . 'farmer/pending.php');
            exit;
        }

        if (($_SESSION['approval_status'] ?? null) === 'rejected') {
            header('Location: ' . BASE_URL . 'farmer/rejected.php');
            exit;
        }

        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}