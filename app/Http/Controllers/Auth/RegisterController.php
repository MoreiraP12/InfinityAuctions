<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ImageController;
use App\Models\Image;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Rules\IsValidAddress;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:30|regex:/^[a-zA-Z\s]{1,30}$/',
            'gender' => ['required', Rule::in(['M', 'F', 'NB', 'O'])],
            'cellphone' => 'required|numeric|digits:9|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'birth_date' => 'required|date|before:-18 years',
            'address' => ['required', 'unique:users', new IsValidAddress],
            'password' => 'required|string|min:6|confirmed',
            'profile_picture' => 'mimes:jpeg,jpg,png,gif'],
            ['birth_date.before' => "You need to be, at least, 18 years old to sign up in our website.",
                'name.regex' => "The name field must consist of only letters and whitespaces, and must have a length between 1 and 30 characters (inclusive).",
                'address.unique' => "The selected address is already in use by another user.",
                'cellphone.unique' => "The selected mobile phone number is already in use by another user."]
        );
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        if (isset($data['profile_picture'])) {
            $imageId = ImageController::store($data['profile_picture'], 'UserImages/', NULL);
        } else {
            $imageId = Image::getDefaultUser()->value('id');
        }
        return User::create([
            'name' => $data['name'],
            'gender' => $data['gender'],
            'cellphone' => $data['cellphone'],
            'email' => $data['email'],
            'birth_date' => $data['birth_date'],
            'address' => $data['address'],
            'password' => bcrypt($data['password']),
            'credits' => 0,
            'is_admin' => FALSE,
            'profile_image' => $imageId
        ]);
    }
}
