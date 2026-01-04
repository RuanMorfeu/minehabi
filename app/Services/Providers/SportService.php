<?php

declare(strict_types=1);

namespace App\Services\Providers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class SportService
{
    public function call()
    {
        $client = new Client;
        $headers = [
            'Accept' => 'application/json',
            'X-API-Key' => 'eyJhbGciOiJSUzI1NiIsImtpZCI6IkhKcDkyNnF3ZXBjNnF3LU9rMk4zV05pXzBrRFd6cEdwTzAxNlRJUjdRWDAiLCJ0eXAiOiJKV1QifQ.eyJhY2Nlc3NfdGllciI6InRyYWRpbmciLCJleHAiOjE5Nzg1NjU4NTQsImlhdCI6MTY2MzIwNTg1NCwianRpIjoiNGQ0NTAxMjQtYjBiYi00ZjYyLTkzNTEtYzgxYjVmNTNjN2E4Iiwic3ViIjoiODVlYTg0YzUtNGYyMi00NmQwLThjMWItMjMwMTJjNTI0YTRjIiwidGVuYW50IjoiY2xvdWRiZXQiLCJ1dWlkIjoiODVlYTg0YzUtNGYyMi00NmQwLThjMWItMjMwMTJjNTI0YTRjIn0.neOOJIQQXpLyr9RnosJ1oTe_8mHCm9bGMMmVcyQeO_eq1NlPPJ_1XVr_Rnmbf-XF6kYWeQRGF4H1yuYxB-QCeVJw9uBtIqzMIELMY6ccUXyLA9htgBaTBlnyOdCJIZZQmwMg2egihX6cGxmYTkr9ZMD5375Jm8nLVhjSw7zTWpWQChKgaVcqPIwezUC4lOaCfB0wF8ExubXWp__GblzzY7m3eBfqu18ajdCM7i0uR-fGvT5_WIcMVJRvDvaC0yqSSqAV6ip31sL6haPQgUrtxqrYRdnFcgNmqZFtBtHWvS49ZXV4e8bUN9Axsf14ouuSEgdj7addeTVQoCebrfpBUA',
            'Authorization' => 'Bearer 85ea84c5-4f22-46d0-8c1b-23012c524a4c',
        ];
        $request = new Request('GET', 'https://sports-api.cloudbet.com/pub/v2/odds/sports', $headers);
        $res = $client->sendAsync($request)->wait();

        return $res->getBody();
    }
}
