<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\DepositRequest;
use Inertia\Inertia;

class DepositController extends Controller
{
    public function index()
    {
        return Inertia::render('Web/Wallet/Deposit/Index');
    }

    public function store(DepositRequest $request): void {}

    public function firstDeposit()
    {
        return Inertia::render('Web/Wallet/Deposit/DepositView');
    }
}
