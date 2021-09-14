<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * @return View
     */
    public function showLogin()
    {
        return view('login.login_form');
    }

    /**
     * @param App\Http\Requests\LoginFormRequest
     * $request
     */
    public function login(LoginFormRequest $request)
    {
        // dd($request->all());
        $credentials= $request->only('email','password');

        //①アカウントがロックされていたら弾く
        $user = User::where('email','=',$credentials['email'])->first();

        if(!is_null($user)){
            if($user->locked_flg===1){
                return back()->withErrors([
                    'danger'=>'アカウントがロックされています。',
                ]);
            }

            if(Auth::attempt($credentials)){
                $request->session()->regenerate();
                //②成功したらエラーアカウントを0にする
                if($user->error_count>0){
                    $user->error_count=0;
                    $user->save();
                }
    
                return redirect()->route('home')->with('success','ログイン成功しました。');
            }
            
            //③ログイン失敗したらエラーアカウントを1増やす
            $user->error_count=$user->error_count+1;
            if($user->error_count>5){
                //④エラーアカウントが6以上の場合はアカウントをロックする
                $user->locked_flg=1;
                $user->save();
                return back()->withErrors([
                    'danger'=>'アカウントがロックされました。解除したい場合は運営者に連絡して下さい。',
                ]);
            }
            $user->save();
    
        }

        return back()->withErrors([
            'danger'=>'メールアドレスが間違っています。',
        ]);
    }

    /**
     * ユーザーをアプリケーションからログアウトさせる
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('showLogin')->with('danger','ログアウトしました。');
    }
}
