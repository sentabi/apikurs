<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{

    public function index(Request $request)
    {

        $token = $request->get('token');
        $bank = $request->get('bank');
        $mataUang = $request->get('matauang');
        $daftarBank = ["bca", "bi", "bjb", "bni", "bri", "btn", "bukopin",
        "cimb", "commonwealth", "danamon", "hsbc", "jtrust", "mandiri",
        "mayapada", "maybank", "mega", "muamalat", "ocbc", "panin",
        "permata", "sinarmas", "uob", "woorisaudara"];

        if (empty($token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'token required!'
            ]);
        }
        if (empty($bank) || !ctype_lower($bank)) {
            return response()->json([
                'status' => 'error',
                'message' => 'bank invalid!'
            ]);
        }
        if (empty($mataUang) || !ctype_lower($mataUang)) {
            return response()->json([
                'status' => 'error',
                'message' => 'matauang invalid!'
            ]);
        }

        $mataUang = strtoupper($mataUang);
        $validasiToken = User::where('token', $token)->first();
        $timestamp = date('Y-m-d');

        if ($validasiToken) {
            if (! $validasiToken->email_verified_at) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'email belum diaktivasi.'
                ]);
            }
            // free user
            if ($validasiToken->member == 'free' && $validasiToken->ratelimit <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'token habis. Upgrade akun anda.'
                ]);
            }

            if ($validasiToken->member == 'premium') {
                $today = \Carbon\Carbon::today();
                $expiredToken = $validasiToken->expired_at;
                // cek kalo uudah lewat tanggalnya minus
                // dd($today->diffInDays($expiredToken,false));
                if ($today->diffInDays($expiredToken, false) < 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Token telah expired.'
                    ]);
                }
            }

            // Lama query di cache dalam detik
            $cacheTime = 60;
            if (in_array($bank, $daftarBank)) {
                try {
                    $kursJual = Cache::tags('kursjual')->remember($bank . '_' . $mataUang, $cacheTime, function () use ($mataUang, $bank) {
                        return \DB::connection('mysql2')->table($bank)->select($mataUang)->where('status', 'jual')->orderBy('created_at', 'desc')->first();
                    });
                    $kursBeli = Cache::tags('kursbeli')->remember($bank . '_' . $mataUang, $cacheTime, function () use ($mataUang, $bank) {
                        return \DB::connection('mysql2')->table($bank)->select($mataUang)->where('status', 'beli')->orderBy('created_at', 'desc')->first();
                    });
                } catch (\Illuminate\Database\QueryException $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'matauang invalid!'
                    ]);
                }

                $validasiToken->decrement('ratelimit');
                // $sisaToken = $validasiToken->ratelimit - 1;

                $pesanSukses = [
                    'status' => "success",
                    'bank' => strtoupper($bank),
                    'matauang' => "$mataUang",
                    'jual' => $kursJual->$mataUang,
                    'beli' => $kursBeli->$mataUang,
                    'timestamp' => "$timestamp",
                ];
                return response()->json($pesanSukses);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'bank invalid!'
                ]);
            }

        // token invalid
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Token invalid!'
            ]);
        }
    }


    public function konversiMataUang(Request $request) {

        $validatedData = \Validator::make($request->all(), [
            'from' => 'required',
            'to' => 'required',
            'amount' => 'required|numeric',
        ]);

        $from = $request->from;
        $to = $request->to;
        $amount = $request->amount;

        // default nilai rupiah
        $IDR = (object) array('IDR' => (float) 1);

        if ($to == 'IDR') {
            $gettoCurrency = $IDR;
        } else {
            $gettoCurrency = \DB::connection('mysql2')->table('bca')->select($to)->where('status', 'beli')->orderBy('created_at', 'desc')->first();
        }

        if ($from == 'IDR') {
            $getfromCurrency = $IDR;
        } else {
            $getfromCurrency = \DB::connection('mysql2')->table('bca')->select($from)->where('status', 'beli')->orderBy('created_at', 'desc')->first();
        }


        // konversi mata uang FROM ke dalam rupiah, dikalikan dengan jumlah
        // hasil perkalian tersebut dibagikan dengan nilai rupiah kurs TO
        // contoh FROM SGD TO USD AMOUNT 10
        // (SGD * AMOUNT)/ USD

        // konversi ke rupiah
        $toIdr = $getfromCurrency->$from * $amount;

        // konversi ke mata uang dipilih
        $convertCurrency = $toIdr / $gettoCurrency->$to;
        // ambil 1 dibelakang koma
        $result = round($convertCurrency, 1);
        $timestamp = \Carbon\Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        return response()->json([
            'status' => "success",
            'from' => $from,
            'to' => $to,
            'amount' => (float) $amount,
            'result' => (float) $result,
            'timestamp' => $timestamp
        ]);
    }


    public function listBank() {
        // tampilkan semua bank yang di support
    }
}
