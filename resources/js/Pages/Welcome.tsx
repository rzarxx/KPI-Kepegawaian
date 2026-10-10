import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, BarChart3, BriefcaseBusiness, ShieldCheck } from 'lucide-react';

import { usePage } from '@inertiajs/react';

export default function Welcome({ auth }: { auth: { user?: { name: string } } }) {
    const { branding } = usePage().props;
    const brand = branding as { app_name: string; app_logo: string | null; primary_color: string; footer_text: string };
    const accentStyle = brand?.primary_color ? { '--brand-color': brand.primary_color } as React.CSSProperties : undefined;
    return (
        <>
            <Head title="Sistem Penilaian Karyawan" />
            <main style={accentStyle} className="flex min-h-screen flex-col bg-slate-50 text-slate-900">
                <header className="mx-auto flex w-full max-w-7xl items-center justify-between px-5 py-5 sm:px-8">
                    <div className="flex items-center gap-3">{brand.app_logo ? <img src={`/storage/${brand.app_logo}`} alt={brand.app_name} className="size-10 object-contain rounded-lg" /> : <ApplicationLogo className="size-10" />}<div><p className="text-sm font-bold">{brand.app_name}</p><p className="text-xs text-slate-500">Sistem Penilaian Karyawan</p></div></div>
                    <Link className="inline-flex min-h-10 items-center gap-2 rounded-lg bg-brand px-4 text-sm font-semibold text-white hover:bg-brand-dark" href={auth.user ? route('dashboard') : route('login')}>{auth.user ? 'Buka Beranda' : 'Masuk'}<ArrowRight size={16} /></Link>
                </header>
                <section className="mx-auto w-full flex-1 grid max-w-7xl items-center gap-10 px-5 py-16 sm:px-8 lg:grid-cols-[1.1fr_0.9fr] lg:py-24">
                    <div>
                        <span className="inline-flex rounded-full bg-brand-soft px-3 py-1 text-sm font-semibold text-brand-dark">Aplikasi internal perusahaan</span>
                        <h1 className="mt-6 max-w-3xl text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">Penilaian karyawan yang terukur, aman, dan mudah ditindaklanjuti.</h1>
                        <p className="mt-5 max-w-2xl text-base leading-7 text-slate-600">Kelola data karyawan, riwayat penempatan, penilaian berkala, catatan masalah, dan laporan dalam satu sistem dengan cakupan akses yang jelas.</p>
                        <div className="mt-8"><Link className="inline-flex min-h-11 items-center gap-2 rounded-lg bg-brand px-5 text-sm font-semibold text-white shadow-sm hover:bg-brand-dark" href={auth.user ? route('dashboard') : route('login')}>{auth.user ? 'Lanjut ke Beranda' : 'Masuk ke Sistem'}<ArrowRight size={17} /></Link></div>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p className="text-sm font-semibold text-slate-900">Fondasi kerja yang terpercaya</p>
                        <div className="mt-5 space-y-4"><Feature icon={<BriefcaseBusiness size={20} />} title="Riwayat kerja tetap utuh" text="Mutasi dan perubahan status tidak menghapus riwayat karyawan." /><Feature icon={<BarChart3 size={20} />} title="Penilaian dapat disesuaikan" text="Komponen, bobot, kriteria, dan periode dapat dikelola sesuai kebutuhan." /><Feature icon={<ShieldCheck size={20} />} title="Akses sesuai kewenangan" text="Izin, aturan kewenangan, dan cakupan organisasi diterapkan di server." /></div>
                    </div>
                </section>
                {brand.footer_text && (
                    <footer className="border-t border-slate-200 bg-white py-4 text-center text-sm text-slate-500">
                        {brand.footer_text}
                    </footer>
                )}
            </main>
        </>
    );
}

function Feature({ icon, title, text }: { icon: React.ReactNode; title: string; text: string }) { return <article className="flex gap-4 rounded-xl border border-slate-200 p-4"><span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-subtle text-brand">{icon}</span><div><h2 className="font-semibold text-slate-900">{title}</h2><p className="mt-1 text-sm leading-6 text-slate-600">{text}</p></div></article>; }
