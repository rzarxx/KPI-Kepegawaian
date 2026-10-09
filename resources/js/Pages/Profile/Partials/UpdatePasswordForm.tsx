import SecurePasswordInput from '@/Components/SecurePasswordInput';
import { Button } from '@/Components/ui/button';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

export default function UpdatePasswordForm({
    className = '',
}: {
    className?: string;
}) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        errors,
        put,
        reset,
        processing,
        recentlySuccessful,
    } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errors) => {
                if (errors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-semibold text-slate-900">Perbarui Kata Sandi</h2>
                <p className="mt-1 text-sm text-slate-500">
                    Gunakan kata sandi yang panjang dan unik untuk menjaga keamanan akun.
                </p>
            </header>

            <form onSubmit={updatePassword} className="mt-6 space-y-5">
                <SecurePasswordInput
                    id="current_password"
                    label="Kata Sandi Saat Ini"
                    name="current_password"
                    value={data.current_password}
                    autoComplete="current-password"
                    error={errors.current_password}
                    onChange={(value) => setData('current_password', value)}
                />

                <SecurePasswordInput
                    id="password"
                    label="Kata Sandi Baru"
                    name="password"
                    value={data.password}
                    autoComplete="new-password"
                    error={errors.password}
                    onChange={(value) => setData('password', value)}
                />

                <SecurePasswordInput
                    id="password_confirmation"
                    label="Konfirmasi Kata Sandi"
                    name="password_confirmation"
                    value={data.password_confirmation}
                    autoComplete="new-password"
                    error={errors.password_confirmation}
                    onChange={(value) => setData('password_confirmation', value)}
                />

                <div className="flex items-center gap-4 pt-1">
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Menyimpan...' : 'Simpan'}
                    </Button>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-slate-500">Tersimpan.</p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
