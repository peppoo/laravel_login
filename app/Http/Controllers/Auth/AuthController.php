<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct(User $user)
    {
        $this->user=$user;
    }

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
        $user = $this->user->getUserByEmail($credentials['email']);

        if(!is_null($user)){
            if($this->user->isAccountLocked($user)){
                return back()->withErrors([
                    'danger'=>'アカウントがロックされています。',
                ]);
            }

            if(Auth::attempt($credentials)){
                $request->session()->regenerate();
                //②成功したらエラーアカウントを0にする
                $this->user->resetErrorCount($user);
    
                return redirect()->route('home')->with('success','ログイン成功しました。');
            }
            
            //③ログイン失敗したらエラーアカウントを1増やす
            $user->error_count=$this->user->addErrorCount($user->error_count);
            //④エラーアカウントが6以上の場合はアカウントをロックする
            if($this->user->lockAccount($user)){
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
