<?php

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

/**
 * Check whether a user is currently logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}


/**
 * Require the user to be logged in.
 *
 * Redirects to login page if they are not authenticated.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| User Information
|--------------------------------------------------------------------------
*/

/**
 * Get the currently logged-in user's ID.
 */
function getUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}


/**
 * Get the currently logged-in user's role.
 */
function getUserRole(): ?string
{
    return $_SESSION['role'] ?? null;
}


/**
 * Get the currently logged-in user's name.
 */
function getUserName(): ?string
{
    return $_SESSION['name'] ?? null;
}


/**
 * Check whether the current user has a specific role.
 */
function hasRole(string $role): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? null) === $role;
}


/**
 * Require the user to have a specific role.
 */
function requireRole(string $role): void
{
    requireLogin();

    if (getUserRole() !== $role) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Farmer Approval
|--------------------------------------------------------------------------
*/

/**
 * Check whether the logged-in farmer has been approved.
 */
function isFarmerApproved(): bool
{
    return hasRole('farmer')
        && ($_SESSION['approval_status'] ?? null) === 'approved';
}


/**
 * Require an approved farmer.
 */
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

        // Unexpected approval status
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

