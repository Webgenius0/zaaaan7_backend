<?php

namespace App\Services\API\Auth;

use App\Helper\Helper;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthService
{

    /**
     * Registers a new user and returns a JWT token upon successful registration.
     *
     * This method accepts user credentials, creates a new user record in the database,
     * and generates a JWT token for authentication if the user is successfully registered.
     * In case of an error during user creation or token generation, an exception is caught,
     * logged, and the method returns null to indicate failure.
     *
     * @param array $credentials An associative array containing the user's registration details:
     *                            - 'name' (string): The full name of the user.
     *                            - 'email' (string): The email address of the user.
     *                            - 'password' (string): The plain-text password of the user, which will be hashed.
     *
     * @return array|null The generated JWT token if registration is successful; otherwise, null if an error occurs.
     *
     * @throws Exception If token generation fails after user creation.
     */
    public function register(array $credentials): array
    {
        try {

            DB::beginTransaction();

            $user = $this->createUser($credentials);

            $optService = new OTPService();
            $otp = $optService->otpSend($user->email, 'email');

            $token = $token = JWTAuth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']]);

            if (!$token) {
                throw new Exception('Token generation failed.', 500);
            }
            DB::commit();

            return ['token' => $token, 'role' => $user->role, 'verify' => false, 'otp' => $otp];
            // return ['id'=>$user->id, 'role' => $user->role, 'verify' => false, 'otp' => $otp];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('AuthService::register -> ' . $e->getMessage());
            throw $e;
        }

    }



    /**
     * Registers a new helper user, creates the user, sends an OTP verification email,
     * and generates a JWT token for authentication.
     *
     * This method performs the following steps:
     * - Begins a database transaction to ensure atomicity.
     * - Creates a new helper user using the provided credentials.
     * - Sends an OTP verification email to the newly created user.
     * - Attempts to generate a JWT token for the user based on their email and password.
     * - Rolls back the transaction if any error occurs, logging the error.
     * - Returns an array containing the JWT token, user role, and a flag indicating
     *   whether the user's email has been verified (set to false in this case).
     *
     * @param array $credentials The user credentials, which include:
     *   - first_name: The user's first name.
     *   - last_name: The user's last name.
     *   - email: The user's email address.
     *   - password: The user's password.
     *   - description: The user's profile description (bio).
     *   - id: The user's ID document file.
     *   - documents: An array of additional document files to be uploaded.
     *   - sub_category_id: The ID of the skill sub-category to attach to the user.
     *
     * @return array The response containing:
     *   - 'token': The JWT token for authentication.
     *   - 'role': The user's role (e.g., 'helper').
     *   - 'verify': A flag indicating whether the email is verified (false by default).
     *
     * @throws Exception If there is an error during the registration process, user creation,
     *                   OTP sending, or token generation.
     */
    public function registerHelper(array $credentials): array
    {
        try {
            DB::beginTransaction();

            $user = $this->createHelper($credentials);

            $optService = new OTPService();

            $otp = $optService->otpSend($user->email, 'email');

            $token = JWTAuth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']]);

            if (!$token) {
                throw new Exception('Token generation failed.', 500);
            }

            DB::commit();

            return ['id'=>$user->id, 'token' => $token, 'role' => $user->role, 'verify' => false, 'otp' => $otp];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('AuthService::register -> ' . $e->getMessage());
            throw $e;
        }
    }



    /**
     * Authenticates a user and returns a JWT token upon successful login.
     *
     * This method accepts user credentials, verifies the user's existence, and checks the
     * provided password against the stored hash. If authentication is successful, a JWT token
     * is generated for the user. If any error occurs during the process, an appropriate exception
     * is thrown with a detailed error message.
     *
     * @param array $credentials An associative array containing the user's login credentials:
     *                            - 'email' (string): The email address of the user.
     *                            - 'password' (string): The plain-text password of the user.
     *
     * @return array The generated JWT token if the login is successful.
     *
     * @throws Exception If any other error occurs, such as token generation failure.
     */
    public function login(array $credentials): array
    {
        try {
            $user = User::where('email', $credentials['email'])->first();

            $token = JWTAuth::fromUser($user);

            if (!$token) {
                throw new Exception('Token generation failed.');
            }

            $verify = false;
            if ($user->email_verified_at) {
                $verify = true;
            }

            return ['id'=>$user->id, 'token' => $token, 'role' => $user->role, 'verify' => $verify];

        } catch (Exception $e) {
            Log::error('AuthService::login -> ' . $e->getMessage());
            throw $e;
        }
    }




    /**
     * Logs out the user by invalidating their JWT token.
     *
     * This method retrieves the current token from the request, invalidates it
     * to log the user out, and handles any exceptions that may arise during
     * the process by logging the error message.
     *
     * @throws Exception If an error occurs while invalidating the token, it will
     * be logged and re-thrown.
     */
    public function logout(): void
    {
        try {
            $token = JWTAuth::getToken();
            JWTAuth::invalidate($token);
        } catch (Exception $e) {
            Log::error('AuthService::logout -> ' . $e->getMessage());
            throw $e;
        }
    }



    /**
     * Create a new user and their associated profile.
     *
     * This method handles the creation of a new user in the system. It accepts an array of credentials,
     * validates the input, creates a new user record in the database, and also creates an associated
     * user profile with additional information such as the user's address.
     *
     * The user's handle is generated using a helper function to ensure uniqueness.
     * The user's password is securely hashed before being stored.
     *
     * If an error occurs during the process, it is logged, and the exception is rethrown for further handling.
     *
     * @param array $credentials An associative array containing the user's credentials, including:
     *      - 'first_name' (string)  The user's first name.
     *      - 'last_name' (string)   The user's last name.
     *      - 'handle' (string)      A unique handle for the user (auto-generated).
     *      - 'email' (string)       The user's email address.
     *      - 'password' (string)    The user's password.
     *      - 'address' (string)     The user's address (for the profile).
     *
     * @return \App\Models\User The newly created user instance.
     *
     * @throws \Exception If any error occurs during the user creation process, it will be logged and rethrown.
     */
    public function createUser(array $credentials, $role = 'user'): mixed
    {
        try {
            $helper = new Helper();
            // creating user
            $user = User::create([
                'first_name' => $credentials['first_name'],
                'last_name' => $credentials['last_name'],
                'handle' => $helper->generateUniqueSlug($credentials['first_name'], 'users', 'handle'),
                'email' => $credentials['email'],
                'password' => Hash::make($credentials['password']),
                'role' => $role,
            ]);
            // creating user profile
            $user->profile()->create([
                'address' => $credentials['address'],
            ]);
            return $user;
        } catch (Exception $e) {
            Log::error('AuthService::createUser-> ' . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Creates a new helper user with associated details including profile, documents, and skills.
     *
     * This method performs the following tasks:
     * - Creates a new user with the provided credentials and a default or specified role.
     * - Generates a unique slug for the user handle.
     * - Creates a user profile with a bio.
     * - Uploads the user's ID document and associates it with the user.
     * - Uploads any additional documents and associates them with the user.
     * - Attaches skills to the user based on the provided sub-category ID.
     *
     * @param array $credentials The user's credentials, including:
     *   - first_name: The user's first name.
     *   - last_name: The user's last name.
     *   - email: The user's email address.
     *   - password: The user's password.
     *   - description: The user's profile description (bio).
     *   - id: The user's ID document file.
     *   - documents: An array of additional document files to be uploaded.
     *   - sub_category_id: The ID of the skill sub-category to attach to the user.
     * @param string $role The role to assign to the user (default is 'helper').
     *
     * @return User The newly created user object.
     *
     * @throws Exception If any error occurs during user creation, profile creation, or document uploads.
     */
    public function createHelper(array $credentials, $role = 'helper'): mixed
    {
        try {
            $helper = new Helper();
            $newUser = User::create([
                'first_name' => $credentials['first_name'],
                'last_name' => $credentials['last_name'],
                'handle' => $helper->generateUniqueSlug($credentials['first_name'], 'users', 'handle'),
                'email' => $credentials['email'],
                'password' => Hash::make($credentials['password']),
                'role' => $role,
            ]);

            // Create profile
            $newUser->profile()->create([
                'bio' => $credentials['description'],
            ]);

            // Upload ID document
            $documentId = Helper::uploadFile($credentials['id'], 'document/' . $newUser->id . '/id');
            $newUser->documents()->create([
                'type' => 'id',
                'url' => $documentId,
            ]);

            // Upload other documents
            foreach ($credentials['documents'] as $document) {
                $documentUrl = Helper::uploadFile($document, 'document/' . $newUser->id . '/documents');
                $newUser->documents()->create([
                    'type' => 'document',
                    'url' => $documentUrl,
                ]);
            }

            // Attach skills
            $newUser->skills()->attach($credentials['sub_category_id']);

            return $newUser;
        } catch (Exception $e) {
            Log::error('AuthService::createUser-> ' . $e->getMessage());
            throw $e;
        }
    }


}
