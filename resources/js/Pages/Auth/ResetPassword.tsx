import AuthSidePanel from '@/Components/AuthSidePanel';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import SecurePasswordInput from '@/Components/SecurePasswordInput';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, BarChart3, KeyRound } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

export default function ResetPassword({ token, email }: { token: string; email: string }) {
    const { data, setData, post, processing, errors, reset } = useForm({ token, email, password: '', password_confirmation: '' });
    const [maskSignal, setMaskSignal] = useState(0);
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        setMaskSignal((value) => value + 1);
        post(route('password.store'), { onFinish: () => reset('password', 'password_confirmation') });
    };

    return (
        <GuestLayout className="max-w-5xl">
            <Head title="Atur Ulang Kata Sandi" />
            <div className="grid min-h-[660px] lg:grid-cols-2">
                <AuthSidePanel title="Buat kata sandi baru" description="Gunakan kata sandi yang kuat dan berbeda dari akun lain agar akses Anda tetap terlindungi." detail={<span>Tautan hanya dapat digunakan untuk akun dan token yang cocok, serta akan kedaluwarsa otomatis.</span>} />
                <section className="flex items-center p-6 sm:p-10">
                    <div className="w-full">
                        <div className="mb-8 flex size-10 items-center justify-center rounded-xl bg-brand text-white lg:hidden"><BarChart3 aria-hidden="true" size={21} /></div>
                        <div className="flex size-11 items-center justify-center rounded-xl bg-green-50 text-green-700"><KeyRound aria-hidden="true" size={22} /></div>
                        <h2 className="mt-5 text-[26px] font-bold tracking-tight text-slate-900">Atur ulang kata sandi</h2>
                        <p className="mt-2 text-sm leading-6 text-slate-500">Masukkan kata sandi baru minimal 8 karakter, lalu konfirmasikan sekali lagi.</p>
                        <form className="mt-8 space-y-5" onSubmit={submit}>
                            <div>
                                <InputLabel className="text-sm font-semibold text-slate-700" htmlFor="email" value="Email akun" />
                                <TextInput id="email" type="email" name="email" value={data.email} className="mt-1 block h-10 w-full rounded-[9px] border-slate-300 bg-slate-50 text-sm text-slate-600 shadow-none" autoComplete="username" readOnly onChange={(event) => setData('email', event.target.value)} />
                                <InputError message={errors.email} className="mt-2" />
                            </div>
                            <SecurePasswordInput key={`password-${maskSignal}`} id="password" label="Kata Sandi Baru" name="password" value={data.password} error={errors.password} autoComplete="new-password" isFocused onChange={(value) => setData('password', value)} />
                            <SecurePasswordInput key={`confirmation-${maskSignal}`} id="password_confirmation" label="Konfirmasi Kata Sandi" name="password_confirmation" value={data.password_confirmation} error={errors.password_confirmation} autoComplete="new-password" onChange={(value) => setData('password_confirmation', value)} />
                            <Button className="w-full" disabled={processing} type="submit">{processing ? 'Menyimpan…' : 'Simpan Kata Sandi Baru'}</Button>
                        </form>
                        <Link className="mt-6 flex min-h-10 items-center justify-center gap-2 text-sm font-semibold text-green-700 hover:text-green-800" href={route('login')}><ArrowLeft size={16} />Kembali ke halaman masuk</Link>
                    </div>
                </section>
            </div>
        </GuestLayout>
    );
}
