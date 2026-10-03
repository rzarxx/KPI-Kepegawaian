import InputError from '@/Components/InputError';
import SecurePasswordInput from '@/Components/SecurePasswordInput';
import { Button } from '@/Components/ui/button';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Konfirmasi Kata Sandi" />

            <div className="mb-4 text-sm text-slate-600">
                Halaman ini berisi tindakan sensitif. Konfirmasikan kata sandi
                Anda sebelum melanjutkan.
            </div>

            <form onSubmit={submit}>
                <SecurePasswordInput
                    id="password"
                    label="Kata Sandi"
                    name="password"
                    value={data.password}
                    autoComplete="current-password"
                    isFocused={true}
                    error={errors.password}
                    onChange={(value) => setData('password', value)}
                />

                <div className="mt-4 flex items-center justify-end">
                    <Button className="ms-4" disabled={processing}>
                        Konfirmasi
                    </Button>
                </div>
            </form>
        </GuestLayout>
    );
}

