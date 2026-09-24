import AuthSidePanel from '@/Components/AuthSidePanel';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, BarChart3, MailCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('password.email'));
    };

    return (
        <GuestLayout className="max-w-5xl">
            <Head title="Lupa Kata Sandi" />
            <div className="grid min-h-[620px] lg:grid-cols-2">
                <AuthSidePanel title="Pulihkan akses akun" description="Tautan pengaturan ulang dikirim hanya ke email akun yang terdaftar dan memiliki masa berlaku terbatas." detail={<span>Untuk keamanan, sistem tidak akan mengungkap apakah suatu alamat email terdaftar.</span>} />
                <section className="flex items-center p-6 sm:p-10">
                    <div className="w-full">
                        <div className="mb-8 flex size-10 items-center justify-center rounded-xl bg-brand text-white lg:hidden"><BarChart3 aria-hidden="true" size={21} /></div>
                        <div className="flex size-11 items-center justify-center rounded-xl bg-green-50 text-green-700"><MailCheck aria-hidden="true" size={22} /></div>
                        <h2 className="mt-5 text-[26px] font-bold tracking-tight text-slate-900">Lupa kata sandi?</h2>
                        <p className="mt-2 text-sm leading-6 text-slate-500">Masukkan email akun Anda. Jika datanya cocok, petunjuk untuk membuat kata sandi baru akan dikirimkan.</p>
                        {status && <div role="status" className="mt-6 rounded-lg border border-green-200 bg-green-50 px-3 py-3 text-sm text-green-800">{status}</div>}
                        <form className="mt-8 space-y-5" onSubmit={submit}>
                            <div>
                                <InputLabel className="text-sm font-semibold text-slate-700" htmlFor="email" value="Email" />
                                <TextInput id="email" type="email" name="email" value={data.email} className="mt-1 block h-10 w-full rounded-[9px] border-slate-300 text-sm shadow-none focus:border-brand focus:ring-brand" autoComplete="email" isFocused onChange={(event) => setData('email', event.target.value)} />
                                <InputError message={errors.email} className="mt-2" />
                            </div>
                            <Button className="w-full" disabled={processing} type="submit">{processing ? 'Mengirim…' : 'Kirim Tautan Atur Ulang'}</Button>
                        </form>
                        <Link className="mt-6 flex min-h-10 items-center justify-center gap-2 text-sm font-semibold text-green-700 hover:text-green-800" href={route('login')}><ArrowLeft size={16} />Kembali ke halaman masuk</Link>
                    </div>
                </section>
            </div>
        </GuestLayout>
    );
}
