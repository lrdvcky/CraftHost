<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Notification;
use App\Models\ReferralCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'ref' => 'nullable|string|exists:referral_codes,code'
        ]);

        $referrerId = null;
        if ($request->ref) {
            $referrer = ReferralCode::where('code', $request->ref)->first();
            $referrerId = $referrer ? $referrer->user_id : null;
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'referrer_id' => $referrerId
        ]);

        // Отправляем письмо подтверждения email
        $this->sendVerificationEmail($user);

        // Приветственное уведомление
        Notification::create([
            'user_id' => $user->id,
            'title'   => 'Добро пожаловать в CraftHost! 🎮',
            'message' => 'Ваш аккаунт создан. Подтвердите email для полного доступа ко всем функциям. Создайте свой первый сервер в разделе Конфигуратор!',
            'type'    => 'info',
        ]);

        return response()->json([
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Неверный логин или пароль.']]);
        }

        return response()->json([
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Выход выполнен успешно']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * POST /api/email/send-verification
     * Отправляет письмо для подтверждения email.
     */
    public function sendVerification(Request $request)
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email уже подтверждён.']);
        }

        $this->sendVerificationEmail($user);

        return response()->json(['message' => 'Письмо для подтверждения отправлено на ' . $user->email]);
    }

    /**
     * POST /api/email/verify
     * Подтверждение email по токену.
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
        ]);

        $record = DB::table('email_verification_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json(['error' => 'Неверная или истёкшая ссылка.'], 422);
        }

        if (!Hash::check($request->token, $record->token)) {
            return response()->json(['error' => 'Неверная или истёкшая ссылка.'], 422);
        }

        // Проверяем срок (24 часа)
        if (Carbon::parse($record->created_at)->addHours(24)->isPast()) {
            DB::table('email_verification_tokens')->where('email', $request->email)->delete();
            return response()->json(['error' => 'Ссылка истекла. Запросите новое письмо.'], 422);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->update(['email_verified_at' => now()]);

            Notification::create([
                'user_id' => $user->id,
                'title'   => 'Email подтверждён ✅',
                'message' => 'Ваш email успешно подтверждён. Теперь вы можете использовать все функции CraftHost.',
                'type'    => 'success',
            ]);
        }

        DB::table('email_verification_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Email успешно подтверждён!']);
    }

    /**
     * POST /api/forgot-password
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Если аккаунт существует, ссылка для сброса пароля отправлена на почту.']);
        }

        // Проверяем подтверждён ли email
        if (!$user->email_verified_at) {
            return response()->json([
                'error' => 'Email не подтверждён. Сначала подтвердите свою почту, чтобы воспользоваться сбросом пароля.',
                'email_not_verified' => true,
            ], 422);
        }

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email'      => $user->email,
            'token'      => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        $resetUrl = config('app.frontend_url', 'http://144.31.48.179') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);

        try {
            Mail::send([], [], function ($message) use ($user, $resetUrl) {
                $message->to($user->email)
                    ->subject('CraftHost — Сброс пароля')
                    ->html(
                        '<div style="font-family:Arial,sans-serif;max-width:500px;margin:0 auto;padding:30px;background:#1a1d23;color:#e0e0e0;border-radius:12px;">'
                        . '<h2 style="color:#55ff55;text-align:center;">⛏ CraftHost</h2>'
                        . '<p>Здравствуйте!</p>'
                        . '<p>Вы запросили сброс пароля. Нажмите на кнопку ниже:</p>'
                        . '<div style="text-align:center;margin:30px 0;">'
                        . '<a href="' . $resetUrl . '" style="background:#55ff55;color:#1a1d23;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:16px;">Сбросить пароль</a>'
                        . '</div>'
                        . '<p style="color:#888;font-size:12px;">Ссылка действительна 60 минут. Если вы не запрашивали сброс — проигнорируйте это письмо.</p>'
                        . '</div>'
                    );
            });
        } catch (\Throwable $e) {
            \Log::error('Failed to send password reset email: ' . $e->getMessage());
            return response()->json(['error' => 'Не удалось отправить письмо. Попробуйте позже.'], 500);
        }

        return response()->json(['message' => 'Если аккаунт существует, ссылка для сброса пароля отправлена на почту.']);
    }

    /**
     * POST /api/reset-password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'token'    => 'required|string',
            'password' => 'required|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json(['error' => 'Неверный или истёкший токен.'], 422);
        }

        if (!Hash::check($request->token, $record->token)) {
            return response()->json(['error' => 'Неверный или истёкший токен.'], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['error' => 'Токен истёк. Запросите сброс пароля повторно.'], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'Пользователь не найден.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Пароль успешно изменён. Теперь вы можете войти.']);
    }

    /**
     * Отправка письма для подтверждения email.
     */
    private function sendVerificationEmail(User $user): void
    {
        DB::table('email_verification_tokens')->where('email', $user->email)->delete();

        $token = Str::random(64);
        DB::table('email_verification_tokens')->insert([
            'email'      => $user->email,
            'token'      => Hash::make($token),
            'created_at' => now(),
        ]);

        $verifyUrl = config('app.frontend_url', 'http://144.31.48.179')
            . '/verify-email?token=' . $token . '&email=' . urlencode($user->email);

        try {
            Mail::send([], [], function ($message) use ($user, $verifyUrl) {
                $message->to($user->email)
                    ->subject('CraftHost — Подтверждение email')
                    ->html(
                        '<div style="font-family:Arial,sans-serif;max-width:500px;margin:0 auto;padding:30px;background:#1a1d23;color:#e0e0e0;border-radius:12px;">'
                        . '<h2 style="color:#55ff55;text-align:center;">⛏ CraftHost</h2>'
                        . '<p>Здравствуйте!</p>'
                        . '<p>Подтвердите свой email, нажав на кнопку ниже:</p>'
                        . '<div style="text-align:center;margin:30px 0;">'
                        . '<a href="' . $verifyUrl . '" style="background:#55ff55;color:#1a1d23;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:16px;">Подтвердить email</a>'
                        . '</div>'
                        . '<p style="color:#888;font-size:12px;">Ссылка действительна 24 часа. Если вы не регистрировались — проигнорируйте это письмо.</p>'
                        . '</div>'
                    );
            });
        } catch (\Throwable $e) {
            \Log::error('Failed to send verification email: ' . $e->getMessage());
        }
    }
}
