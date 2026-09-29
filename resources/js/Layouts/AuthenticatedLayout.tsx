import ApplicationLogo from '@/Components/ApplicationLogo';
import { Button } from '@/Components/ui/button';
import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    Building2,
    ChevronDown,
    CircleAlert,
    CircleCheck,
    FileChartColumn,
    LogOut,
    Menu,
    Network,
    Palette,
    ScrollText,
    TriangleAlert,
    UserCog,
    UserRound,
    UsersRound,
    X,
} from 'lucide-react';
import { PropsWithChildren, ReactNode, useState } from 'react';

type NavItem = { label: string; href: string; active: boolean; icon: ReactNode };

export default function AuthenticatedLayout({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth, flash, impersonation, branding } = usePage().props;
    const { user, access, abilities, unreadNotifications } = auth;
    const activeImpersonation = impersonation as {
        active: boolean;
        target_name: string;
    } | null;
    const brand = branding as { app_name: string; app_logo: string | null; primary_color: string; footer_text: string };
    const [menuOpen, setMenuOpen] = useState(false);
    const [accountOpen, setAccountOpen] = useState(false);
    const nav: NavItem[] = [
        ...(abilities.dashboardView
            ? [{ label: 'Beranda', href: route('dashboard'), active: route().current('dashboard'), icon: <Building2 size={18} /> }]
            : []),
        ...(abilities.employeeView
            ? [{ label: 'Karyawan', href: route('employees.index'), active: route().current('employees.*'), icon: <UsersRound size={18} /> }]
            : []),
        ...(abilities.incidentView
            ? [{ label: 'Karyawan Bermasalah', href: route('employees.incidents.overview'), active: route().current('employees.incidents.*'), icon: <TriangleAlert size={18} /> }]
            : []),
        ...(abilities.evaluationView
            ? [{ label: 'Penilaian', href: route('evaluations.configuration'), active: route().current('evaluations.*') || route().current('evaluation-*'), icon: <UserRound size={18} /> }]
            : []),
        ...(abilities.reportView
            ? [{ label: 'Laporan', href: route('reports.index'), active: route().current('reports.*'), icon: <FileChartColumn size={18} /> }]
            : []),
        ...(abilities.auditView
            ? [{ label: 'Audit Aktivitas', href: route('audit.index'), active: route().current('audit.*'), icon: <ScrollText size={18} /> }]
            : []),
        {
            label: 'Notifikasi',
            href: route('notifications.index'),
            active: route().current('notifications.*'),
            icon: (
                <span className="relative">
                    <Bell size={18} />
                    {unreadNotifications > 0 && (
                        <span className="absolute -right-2 -top-2 min-w-4 rounded-full bg-red-600 px-1 text-center text-[10px] font-bold leading-4 text-white">
                            {Math.min(unreadNotifications, 99)}
                        </span>
                    )}
                </span>
            ),
        },
        ...(abilities.organizationView
            ? [{ label: 'Organisasi', href: route('organization.index'), active: route().current('organization.*'), icon: <Network size={18} /> }]
            : []),
        ...(abilities.userView
            ? [{ label: 'Pengguna dan Akses', href: route('users.index'), active: route().current('users.*'), icon: <UserCog size={18} /> }]
            : []),
        ...(abilities.settingsManage
            ? [{ label: 'Tampilan Sistem', href: route('settings.appearance'), active: route().current('settings.*'), icon: <Palette size={18} /> }]
            : []),
    ] as NavItem[];

    const accentStyle = brand.primary_color && brand.primary_color !== '#16A34A'
        ? { '--brand-color': brand.primary_color } as React.CSSProperties
        : undefined;

    return (
        <div className="min-h-screen bg-slate-50 text-slate-800" style={accentStyle}>
            <aside className="fixed inset-y-0 left-0 z-30 hidden w-60 flex-col border-r border-slate-200 bg-white lg:flex">
                <BrandHeader appName={brand.app_name} appLogo={brand.app_logo} />
                <nav className="flex-1 overflow-y-auto px-3 py-4">
                    <p className="px-3 pb-2 text-xs font-semibold tracking-wide text-slate-400">MENU UTAMA</p>
                    {nav.map((item) => <NavigationLink item={item} key={item.href} />)}
                </nav>
                {brand.footer_text && (
                    <div className="border-t border-slate-100 px-5 py-3">
                        <p className="text-xs text-slate-400">{brand.footer_text}</p>
                    </div>
                )}
            </aside>
            <header className="sticky top-0 z-20 flex min-h-16 items-center justify-between border-b border-slate-200 bg-white px-4 py-2 lg:pl-[17rem] lg:pr-8">
                <Button variant="ghost" className="lg:hidden" aria-label="Buka menu" onClick={() => setMenuOpen(true)}>
                    <Menu size={20} />
                </Button>
                <div className="hidden lg:block" />
                <div className="relative">
                    <button
                        type="button"
                        aria-expanded={accountOpen}
                        onClick={() => setAccountOpen((value) => !value)}
                        className="flex min-h-11 items-center gap-2 rounded-lg px-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                    >
                        <span className="flex size-8 items-center justify-center rounded-full bg-green-100 text-green-800"><UserRound size={16} /></span>
                        <span className="hidden text-left sm:block">
                            <span className="block">{user.name}</span>
                            {access && (
                                <span className="block text-xs text-slate-400">{access.role}</span>
                            )}
                        </span>
                        <ChevronDown className="hidden text-slate-400 sm:block" size={16} />
                    </button>
                    {accountOpen && (
                        <div className="absolute right-0 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                            {access && (
                                <div className="mb-2 border-b border-slate-100 px-2 pb-2">
                                    <p className="text-xs font-semibold text-green-800">{access.role}</p>
                                    <p className="mt-1 text-xs text-slate-500">Cakupan: {access.scopeLabels.join('; ')}</p>
                                </div>
                            )}
                            {!activeImpersonation?.active && (
                                <Link href={route('profile.edit')} className="flex min-h-10 items-center rounded-lg px-3 text-sm hover:bg-slate-50">Profil</Link>
                            )}
                            {!activeImpersonation?.active && (
                                <Link href={route('logout')} method="post" as="button" className="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-left text-sm text-red-700 hover:bg-red-50">
                                    <LogOut size={16} />Keluar
                                </Link>
                            )}
                        </div>
                    )}
                </div>
            </header>
            {activeImpersonation?.active && (
                <div className="sticky top-16 z-10 flex items-center justify-between gap-3 border-b border-blue-200 bg-blue-50 px-4 py-2 text-sm text-blue-950 lg:pl-[17rem] lg:pr-8">
                    <span>Anda sedang menggunakan akses {activeImpersonation.target_name}.</span>
                    <Link href={route('impersonation.end')} method="post" as="button" className="min-h-10 rounded-lg border border-blue-300 bg-white px-3 font-semibold text-blue-800 hover:bg-blue-100">Kembali ke akun Super Admin</Link>
                </div>
            )}
            {menuOpen && (
                <div className="fixed inset-0 z-40 bg-slate-950/30 lg:hidden" onClick={() => setMenuOpen(false)}>
                    <aside className="h-full w-72 bg-white shadow-xl" onClick={(event) => event.stopPropagation()}>
                        <div className="flex items-center justify-between">
                            <BrandHeader appName={brand.app_name} appLogo={brand.app_logo} />
                            <Button variant="ghost" aria-label="Tutup menu" onClick={() => setMenuOpen(false)}><X size={20} /></Button>
                        </div>
                        {access && (
                            <div className="mx-4 rounded-lg bg-slate-50 p-3">
                                <p className="text-xs font-semibold text-green-800">{access.role}</p>
                                <p className="mt-1 text-xs text-slate-500">{access.scopeLabels.join('; ')}</p>
                            </div>
                        )}
                        <nav className="px-3 py-4">{nav.map((item) => <NavigationLink item={item} key={item.href} onClick={() => setMenuOpen(false)} />)}</nav>
                    </aside>
                </div>
            )}
            {flash?.success && <FlashBanner type="success" message={flash.success} />}
            {flash?.error && <FlashBanner type="error" message={flash.error} />}
            {header && <div className="border-b border-slate-200 bg-white px-4 py-5 lg:pl-[17rem] lg:pr-8">{header}</div>}
            <main className="lg:pl-60">{children}</main>
        </div>
    );
}

function FlashBanner({ type, message }: { type: 'success' | 'error'; message: string }) {
    const success = type === 'success';

    return (
        <div className="px-4 pt-4 lg:pl-[17rem] lg:pr-8" role={success ? 'status' : 'alert'}>
            <div className={`flex items-start gap-3 rounded-xl border p-4 text-sm ${success ? 'border-green-200 bg-green-50 text-green-900' : 'border-red-200 bg-red-50 text-red-900'}`}>
                {success ? <CircleCheck className="mt-0.5 shrink-0" size={18} /> : <CircleAlert className="mt-0.5 shrink-0" size={18} />}
                <p>{message}</p>
            </div>
        </div>
    );
}

function BrandHeader({ appName, appLogo }: { appName: string; appLogo: string | null }) {
    return (
        <Link href={route('dashboard')} className="flex h-16 items-center gap-3 px-5">
            {appLogo ? (
                <img src={`/storage/${appLogo}`} alt={appName} className="size-8 shrink-0 rounded-lg object-contain" />
            ) : (
                <ApplicationLogo className="size-8 shrink-0" />
            )}
            <span>
                <span className="block text-sm font-bold text-slate-900">{appName}</span>
                <span className="block text-xs text-slate-500">Sistem Penilaian</span>
            </span>
        </Link>
    );
}

function NavigationLink({ item, onClick }: { item: NavItem; onClick?: () => void }) {
    return (
        <Link href={item.href} onClick={onClick} className={`mb-1 flex min-h-10 items-center gap-3 rounded-lg px-3 text-sm font-medium transition ${item.active ? 'bg-green-50 text-green-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'}`}>
            {item.icon}{item.label}
        </Link>
    );
}