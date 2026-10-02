import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import SecurePasswordInput from '@/Components/SecurePasswordInput';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { BarChart3, ShieldCheck, UsersRound } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { branding } = usePage().props;
    const brand = branding as { app_name: string; app_logo: string | null; primary_color: string; footer_text: string };
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });
    const [maskSignal, setMaskSignal] = useState(0);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        setMaskSignal((value) => value + 1);

        post(route('login'), {
            onFinish: () => {
                reset('password');
            },
        });
    };

    return (
        <GuestLayout className="max-w-5xl">
            <Head title="Masuk" />

            <div className="grid min-h-[620px] lg:grid-cols-2">
                <section className="hidden flex-col justify-between bg-brand p-10 text-white lg:flex">
                    <div>
                        {brand.app_logo ? <img src={`/storage/${brand.app_logo}`} alt={brand.app_name} className="size-11 object-contain rounded-xl bg-white/10 p-1" /> : <div className="flex size-11 items-center justify-center rounded-xl bg-white/15"><BarChart3 aria-hidden="true" size={24} /></div>}
                        <p className="mt-8 text-sm font-semibold tracking-wide text-white/80 uppercase">{brand.app_name}</p>
                        <h1 className="mt-3 max-w-sm text-3xl font-bold leading-tight">Sistem Penilaian Karyawan</h1>
                        <p className="mt-4 max-w-sm text-sm leading-6 text-white/60">
                            Kelola data, penilaian, dan tindak lanjut karyawan dalam satu sistem yang aman.
                        </p>
                    </div>

                    <div className="space-y-4 text-sm text-white/60">
                        <div className="flex items-center gap-3"><ShieldCheck aria-hidden="true" size={18} /> Akses berdasarkan peran dan scope organisasi</div>
                        <div className="flex items-center gap-3"><UsersRound aria-hidden="true" size={18} /> Riwayat karyawan tetap terjaga</div>
                    </div>
                </section>

                <section className="flex items-center p-6 sm:p-10">
                    <div className="w-full">
                        <div className="mb-8 lg:hidden">
                            {brand.app_logo ? <img src={`/storage/${brand.app_logo}`} alt={brand.app_name} className="size-10 object-contain rounded-xl" /> : <div className="flex size-10 items-center justify-center rounded-xl bg-brand text-white"><BarChart3 aria-hidden="true" size={21} /></div>}
                        </div>
                        <h2 className="text-[26px] font-bold tracking-tight text-slate-900">Masuk ke akun Anda</h2>
                        <p className="mt-2 text-sm text-slate-500">Gunakan email dan kata sandi yang terdaftar.</p>

                        {status && <div className="mt-6 rounded-lg bg-brand-subtle px-3 py-2 text-sm text-brand-dark">{status}</div>}

                        <form className="mt-8 space-y-5" onSubmit={submit}>
                            <div>
                                <InputLabel className="text-sm font-semibold text-slate-700" htmlFor="email" value="Email" />

                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="mt-1 block h-10 w-full rounded-[9px] border-slate-300 text-sm shadow-none focus:border-brand focus:ring-brand"
                                    autoComplete="username"
                                    isFocused={true}
                                    onChange={(event) => setData('email', event.target.value)}
                                />

                                <InputError message={errors.email} className="mt-2" />
                            </div>

                            <SecurePasswordInput
                                key={`password-${maskSignal}`}
                                id="password"
                                label="Kata Sandi"
                                name="password"
                                value={data.password}
                                error={errors.password}
                                autoComplete="current-password"
                                labelAction={canResetPassword ? (
                                        <Link className="text-sm font-medium text-brand hover:text-brand-dark" href={route('password.request')}>
                                            Lupa kata sandi?
                                        </Link>
                                ) : null}
                                onChange={(value) => setData('password', value)}
                            />

                            <label className="flex min-h-10 items-center gap-2 text-sm text-slate-600">
                                <input
                                    checked={data.remember}
                                    className="size-4 rounded border-slate-300 text-brand focus:ring-brand"
                                    name="remember"
                                    onChange={(event) => setData('remember', event.target.checked)}
                                    type="checkbox"
                                />
                                Ingat saya di perangkat ini
                            </label>

                            <Button className="w-full" disabled={processing} type="submit">
                                {processing ? 'Memproses…' : 'Masuk'}
                            </Button>
                        </form>
                    </div>
                </section>
            </div>
        </GuestLayout>
    );
}
