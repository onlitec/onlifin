import * as React from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import {
    Home,
    ArrowLeftRight,
    Building2,
    CreditCard,
    FileText,
    TrendingUp,
    Settings,
    ChevronDown,
    LogOut,
    Users,
    DollarSign
} from 'lucide-react';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar
} from '@/components/ui/sidebar';
import {
    Collapsible,
    CollapsibleContent,
} from '@/components/ui/collapsible';
import { useAuth } from 'miaoda-auth-react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useFinanceScope } from '@/hooks/useFinanceScope';
import { useAuthProfile } from '@/contexts/AuthProfileContext';
import {
    canAccessAdministration,
    canAccessPlatformSettings,
    getAccessRoleLabel,
} from '@/lib/access';

export function OnlifinSidebar() {
    const location = useLocation();
    const navigate = useNavigate();
    const { logout, user } = useAuth();
    const { profile } = useAuthProfile();
    const { state } = useSidebar();
    const { isPJ, companyId } = useFinanceScope();
    const [openMenus, setOpenMenus] = React.useState<Record<string, boolean>>({});
    const userLabel = profile?.full_name?.trim() || user?.email?.split('@')[0] || 'usuario';
    const canManageAdministration = canAccessAdministration(profile, user as any);
    const canManagePlatformSettings = canAccessPlatformSettings(profile, user as any);
    const userRoleLabel = getAccessRoleLabel(profile, user as any);
    const userInitials = userLabel
        .split(/[._\s-]+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() || '')
        .join('') || 'ON';

    const prefix = isPJ && companyId ? `/pj/${companyId}` : '/pf';

    const menuItems = [
        { title: 'Painel', icon: Home, path: prefix },
        { title: 'Contas', icon: Building2, path: `${prefix}/accounts` },
        { title: 'Cartões', icon: CreditCard, path: `${prefix}/cards` },
        {
            title: 'Transações', icon: ArrowLeftRight, path: `${prefix}/transactions`, subItems: [
                { title: 'Listagem', path: `${prefix}/transactions` },
                { title: 'Contas a Pagar', path: `${prefix}/bills-to-pay` },
                { title: 'Contas a Receber', path: `${prefix}/bills-to-receive` },
                { title: 'Importar Extrato', path: `${prefix}/import-statements` },
                { title: 'Conciliação', path: `${prefix}/reconciliation` }
            ]
        },
        { title: 'Pessoas', icon: Users, path: `${prefix}/people` },
        { title: 'Dívidas', icon: DollarSign, path: `${prefix}/debts` },
        { title: 'Previsão Financeira', icon: TrendingUp, path: `${prefix}/forecast` },
        { title: 'Empresas', icon: Building2, path: '/companies' },
        { title: 'Relatórios', icon: FileText, path: `${prefix}/reports` },
        { title: 'Preferências', icon: Settings, path: '/preferences' },
        ...(canManagePlatformSettings ? [{
            title: 'Configurações', icon: Settings, path: '/settings', subItems: [
                { title: 'Plataforma', path: '/settings' },
                { title: 'Gestão de Usuários', path: '/user-management' },
                { title: 'Configuração IA', path: '/ai-admin' }
            ]
        }] : []),
        ...(canManageAdministration ? [{
            title: 'Administração', icon: Settings, path: '/admin-general', subItems: [
                { title: 'Geral', path: '/admin-general' },
                ...(canManagePlatformSettings
                    ? [
                        { title: 'Notificações', path: '/admin-notifications' },
                        { title: 'Categorias', path: '/categories' }
                    ]
                    : [])
            ]
        }] : [])
    ];

    const isActive = (path: string) => {
        if (path === '/pf' || path === `/pj/${companyId}`) {
            return location.pathname === path;
        }
        return location.pathname.startsWith(path);
    };

    const toggleMenu = (title: string) => {
        setOpenMenus(prev => ({ ...prev, [title]: !prev[title] }));
    };

    return (
        <Sidebar collapsible="icon" className="border-r-2 border-slate-300 bg-white">
            <SidebarHeader className={`h-20 flex items-center mb-2 ${state === 'collapsed' ? 'justify-center px-2' : 'px-6'}`}>
                <div className={`flex items-center group cursor-pointer ${state === 'collapsed' ? 'justify-center' : 'gap-3'}`} onClick={() => navigate(prefix)}>
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg transition-transform group-hover:scale-105">
                        <span className="text-xl font-bold">O</span>
                    </div>
                    {state === 'expanded' && (
                        <div className="flex flex-col">
                            <span className="text-xl font-bold tracking-tight text-slate-900 leading-none">
                                OnliFin
                            </span>
                        </div>
                    )}
                </div>
            </SidebarHeader>

            <SidebarContent className={state === 'collapsed' ? 'px-2' : 'px-3'}>
                <SidebarGroup>
                    <SidebarMenu className="gap-1.5">
                        {menuItems.map((item) => {
                            const isCurrentActive = isActive(item.path);

                            if (item.subItems) {
                                const isSubActive = item.subItems.some(sub => location.pathname === sub.path || location.pathname.startsWith(`${sub.path}/`));
                                const isOpen = openMenus[item.title] || isSubActive;

                                if (state === 'collapsed') {
                                    return (
                                        <SidebarMenuItem key={item.title}>
                                            <SidebarMenuButton
                                                asChild
                                                tooltip={item.title}
                                                className={`h-11 rounded-xl transition-all ${isSubActive ? 'bg-blue-600 text-white shadow-md hover:bg-blue-700 hover:text-white' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'}`}
                                            >
                                                <Link to={item.path} className="flex items-center justify-center">
                                                    <item.icon className="h-5 w-5 shrink-0" />
                                                    <span className="sr-only">{item.title}</span>
                                                </Link>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    );
                                }

                                return (
                                    <Collapsible
                                        key={item.title}
                                        open={isOpen}
                                        onOpenChange={() => toggleMenu(item.title)}
                                    >
                                        <SidebarMenuItem>
                                            <div className="flex items-center gap-1">
                                                <SidebarMenuButton
                                                    asChild
                                                    tooltip={item.title}
                                                    className={`h-11 rounded-xl transition-all flex-1 ${isSubActive ? 'bg-blue-600 text-white shadow-md hover:bg-blue-700 hover:text-white' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'}`}
                                                >
                                                    <Link to={item.path} className="flex items-center gap-3">
                                                        <item.icon className="h-5 w-5" />
                                                        <span className="font-bold text-sm">{item.title}</span>
                                                    </Link>
                                                </SidebarMenuButton>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className={`h-11 w-11 rounded-xl transition-all ${isOpen ? 'bg-blue-50 text-blue-700 hover:bg-blue-100' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'}`}
                                                    onClick={() => toggleMenu(item.title)}
                                                >
                                                    <ChevronDown className={`h-4 w-4 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
                                                </Button>
                                            </div>
                                            <CollapsibleContent>
                                                <SidebarMenuSub className="ml-4 pl-4 border-l border-slate-100 space-y-1 mt-1">
                                                    {item.subItems.map((subItem) => (
                                                        <SidebarMenuSubItem key={subItem.title}>
                                                            <SidebarMenuSubButton asChild isActive={location.pathname === subItem.path}>
                                                                <Link to={subItem.path} className="font-medium text-xs py-2 h-auto">
                                                                    {subItem.title}
                                                                </Link>
                                                            </SidebarMenuSubButton>
                                                        </SidebarMenuSubItem>
                                                    ))}
                                                </SidebarMenuSub>
                                            </CollapsibleContent>
                                        </SidebarMenuItem>
                                    </Collapsible>
                                );
                            }

                            return (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton
                                        asChild
                                        tooltip={item.title}
                                        className={`h-11 rounded-xl transition-all ${isCurrentActive ? 'bg-blue-600 text-white shadow-md hover:bg-blue-700 hover:text-white' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'}`}
                                    >
                                        <Link to={item.path} className={`flex items-center ${state === 'collapsed' ? 'justify-center' : 'gap-3'}`}>
                                            <item.icon className="h-5 w-5" />
                                            {state === 'expanded' ? (
                                                <span className="font-bold text-sm">{item.title}</span>
                                            ) : (
                                                <span className="sr-only">{item.title}</span>
                                            )}
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            );
                        })}
                    </SidebarMenu>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter className={`border-t-2 border-slate-300/40 mt-auto ${state === 'collapsed' ? 'p-2' : 'p-4'}`}>
                <div className={`flex ${state === 'collapsed' ? 'flex-col items-center justify-center gap-2 px-0' : 'items-center gap-3 px-2'}`}>
                    <Avatar className="h-10 w-10 border border-slate-200">
                        <AvatarFallback className="bg-slate-100 text-blue-600 font-bold">{userInitials}</AvatarFallback>
                    </Avatar>
                    {state === 'expanded' && (
                        <div className="flex-1 min-w-0 mr-2">
                            <p className="text-sm font-bold text-slate-900 truncate">{userLabel}</p>
                            <p className="text-xs text-slate-500 font-medium truncate uppercase tracking-wider">{userRoleLabel}</p>
                        </div>
                    )}
                    <Button variant="ghost" size="icon" className="shrink-0 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg" onClick={() => logout()} title="Sair">
                        <LogOut className="h-5 w-5" />
                    </Button>
                </div>
            </SidebarFooter>
        </Sidebar>
    );
}
