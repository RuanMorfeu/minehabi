<?php

namespace App\Http\Controllers {

    use App\Models\SystemWallet;

    /*** SystemWalletController
     */
    class SystemWalletController extends Controller
    {
        /**
         * @return void
         */
        public function Bet($value)
        {
            $wallet = SystemWallet::where('id', 1)->first();
            $wallet->increment('balance', $value);
        }

        /**
         * @return void
         */
        public function Pay($value)
        {
            $wallet = SystemWallet::where('id', 1)->first();
            if ($wallet->balance >= $value) {
                $wallet->decrement('balance', $value);
            } else {
                $wallet->decrement('balance', 0);
            }
        }

        /**    * Balance
         */
        public function Balance(): ?float
        {
            $sysWallet = SystemWallet::select('balance')->where('id', 1)->first();

            if ($sysWallet) {
                return floatval($sysWallet->balance);
            }

            return null;
        }

        /**
         * @return void
         */
        public function CanPay($value) {}

        /**    * @return null
         */
        public function Config()
        {
            $sysWallet = SystemWallet::where('id', 1)->first();

            if ($sysWallet) {
                return $sysWallet;
            }

            return null;
        }
    }
}
