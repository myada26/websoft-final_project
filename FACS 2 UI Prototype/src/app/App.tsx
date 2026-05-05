import React, { useState } from 'react';
import {
  Search, Bell, Settings, Users, Building, Building2,
  Calendar, Layers, CheckCircle2, ChevronUp, ChevronDown,
  LayoutDashboard, FileText, CreditCard, XCircle, FileClock,
  BookOpen, Lock, LogOut, Check, ArrowRight, Plus, Upload, 
  Download, Eye, Edit, Trash2, MoreVertical, Filter, ArrowLeft, AlertCircle, UploadCloud, X
} from 'lucide-react';

export default function App() {
  const [userRole, setUserRole] = useState<'login' | 'admin' | 'org'>('login');
  const [currentPage, setCurrentPage] = useState<string>('a-colleges');

  if (userRole === 'login') {
    return <LoginScreen onLogin={(role) => {
      setUserRole(role);
      setCurrentPage(role === 'admin' ? 'a-colleges' : 'o-dashboard');
    }} />;
  }

  return (
    <div className="flex h-screen w-full bg-[#f0f3f1] text-[#0f1f17] font-sans">
      <Sidebar 
        role={userRole} 
        setRole={setUserRole} 
        currentPage={currentPage}
        setCurrentPage={setCurrentPage}
      />
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        <Topbar role={userRole} currentPage={currentPage} />
        <main className="flex-1 overflow-y-auto p-6">
          <PageContent currentPage={currentPage} role={userRole} />
        </main>
      </div>
    </div>
  );
}

// ------------------------------------------------------------------
// REUSABLE UI COMPONENTS
// ------------------------------------------------------------------

const Button = ({ 
  children, variant = 'primary', icon: Icon, onClick, className = '' 
}: { 
  children: React.ReactNode, variant?: 'primary' | 'outline' | 'ghost', icon?: any, onClick?: () => void, className?: string 
}) => {
  const baseStyles = "px-4 py-2 rounded-xl text-[13.5px] font-bold flex items-center justify-center gap-2 transition-all shadow-sm";
  const variants = {
    primary: "bg-[#1a7a41] hover:bg-[#27a05a] text-white border-2 border-transparent shadow-[#1a7a41]/20",
    outline: "bg-white border-2 border-[#dde8e1] hover:border-[#1a7a41] hover:text-[#1a7a41] text-[#4a6356]",
    ghost: "bg-transparent hover:bg-[#dde8e1] text-[#4a6356] border-2 border-transparent shadow-none"
  };

  return (
    <button onClick={onClick} className={`${baseStyles} ${variants[variant]} ${className}`}>
      {Icon && <Icon className="w-4 h-4 shrink-0" />}
      {children}
    </button>
  );
};

const IconButton = ({ icon: Icon, variant = 'default', title }: { icon: any, variant?: 'default' | 'danger' | 'success', title?: string }) => {
  const variants = {
    default: "text-[#8aa89a] hover:bg-[#dde8e1] hover:text-[#1a7a41]",
    danger: "text-[#8aa89a] hover:bg-red-50 hover:text-red-600",
    success: "text-[#8aa89a] hover:bg-[#e6f4ec] hover:text-[#1a7a41]"
  };
  return (
    <button title={title} className={`p-1.5 rounded-lg transition-colors ${variants[variant]}`}>
      <Icon className="w-4 h-4" />
    </button>
  );
};

const PageHeader = ({ title, subtitle, actions }: { title: string, subtitle: string, actions?: any[] }) => (
  <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
    <div>
      <h2 className="text-[22px] font-bold text-[#0f1f17]">{title}</h2>
      <p className="text-[13.5px] text-[#4a6356] mt-1 font-medium">{subtitle}</p>
    </div>
    {actions && (
      <div className="flex flex-wrap gap-2.5">
        {actions.map((action, i) => (
          <Button key={i} variant={action.variant} icon={action.icon} onClick={action.onClick}>
            {action.label}
          </Button>
        ))}
      </div>
    )}
  </div>
);

// ------------------------------------------------------------------
// LOGIN & SHELL
// ------------------------------------------------------------------

const LoginScreen = ({ onLogin }: { onLogin: (role: 'admin' | 'org') => void }) => {
  const [username, setUsername] = useState('admin.ssc');

  const ProfilePlaceholder = ({ name, role }: { name: string, role: string }) => (
    <div className="flex flex-col items-center group cursor-pointer relative">
      <div className="w-[76px] h-[76px] rounded-full bg-gradient-to-b from-white to-[#f0f3f1] shadow-[0_4px_20px_rgba(26,122,65,0.08)] flex items-center justify-center mb-3 text-[#1a7a41] group-hover:-translate-y-1 transition-all duration-300 ring-4 ring-white relative z-10">
         <Users className="w-7 h-7 opacity-50 group-hover:opacity-100 group-hover:scale-110 transition-all" />
      </div>
      <h4 className="text-[14.5px] font-bold text-[#0f1f17] group-hover:text-[#1a7a41] transition-colors whitespace-nowrap">{name}</h4>
      <p className="text-[11px] text-[#8aa89a] font-bold uppercase tracking-wider whitespace-nowrap mt-0.5">{role}</p>
    </div>
  );

  return (
    <div className="flex w-full h-screen bg-white overflow-hidden">
      {/* Left Side: Login Panel */}
      <div className="w-full lg:w-[45%] xl:w-[40%] bg-[#0d4a1e] flex items-center justify-center p-8 relative shrink-0 shadow-2xl z-10 h-full">
        {/* Background decorations */}
        <div className="absolute top-0 left-0 w-full h-full overflow-hidden opacity-10 pointer-events-none">
          <div className="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-white blur-3xl"></div>
          <div className="absolute bottom-12 -right-12 w-64 h-64 rounded-full bg-white blur-3xl"></div>
        </div>

        <div className="bg-white rounded-2xl p-10 w-full max-w-[420px] shadow-2xl relative z-10">
          <div className="flex items-center gap-3 mb-8">
            <div className="w-10 h-10 bg-[#d4a42a] rounded-lg flex items-center justify-center shrink-0 shadow-sm">
              <Lock className="w-5 h-5 text-[#0d4a1e]" />
            </div>
            <div>
              <strong className="block text-[16px] font-bold text-[#0f1f17] leading-tight">FCATS</strong>
              <span className="text-[12px] text-[#8aa89a] leading-tight">Secure Access</span>
            </div>
          </div>
          <h1 className="text-[24px] font-bold mb-1 text-[#0f1f17]">Welcome back</h1>
          <p className="text-[14px] text-[#4a6356] mb-8">Enter your credentials to access the system.</p>
          
          <div className="mb-5">
            <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Username or Email</label>
            <input 
              type="text" 
              className="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-[#f8fbf9] text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] focus:bg-white transition-colors"
              value={username} onChange={e => setUsername(e.target.value)} placeholder="e.g. admin.ssc"
            />
          </div>
          <div className="mb-8">
            <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Password</label>
            <input 
              type="password" 
              className="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-[#f8fbf9] text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] focus:bg-white transition-colors"
              defaultValue="••••••••"
            />
          </div>
          <button 
            onClick={() => onLogin(username.toLowerCase().includes('org') ? 'org' : 'admin')}
            className="w-full py-3.5 bg-[#1a7a41] hover:bg-[#27a05a] text-white rounded-xl text-[14px] font-bold shadow-md shadow-[#1a7a41]/20 transition-all flex items-center justify-center gap-2"
          >
            Sign In <ArrowRight className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Right Side: Information Panel (Creative & Boxless) */}
      <div className="hidden lg:flex flex-1 bg-gradient-to-br from-[#f8fbf9] to-[#eaf0ec] relative flex-col items-center justify-center p-12 h-full overflow-hidden">
        
        {/* Soft Abstract Background Elements */}
        <div className="absolute top-0 right-0 w-[600px] h-[600px] bg-gradient-to-br from-[#1a7a41]/5 to-transparent rounded-full blur-3xl pointer-events-none"></div>
        <div className="absolute bottom-[-100px] left-[-100px] w-[600px] h-[600px] bg-gradient-to-tr from-[#d4a42a]/5 to-transparent rounded-full blur-3xl pointer-events-none"></div>

        <div className="w-full max-w-2xl flex flex-col items-center relative z-10">
          
          {/* Elegant Typography Title */}
          <div className="mb-12 text-center">
            <h2 className="text-[32px] md:text-[40px] font-black text-[#0f1f17] tracking-tight leading-[1.15] mb-4">
              Fee Collection <span className="text-[#1a7a41]">&amp;</span><br/>Tracking System
            </h2>
            <div className="flex items-center justify-center gap-4">
              <div className="h-[1px] w-16 bg-gradient-to-r from-transparent to-[#8aa89a]"></div>
              <span className="text-[#8aa89a] font-bold tracking-widest uppercase text-[11px]">FCATS Platform</span>
              <div className="h-[1px] w-16 bg-gradient-to-l from-transparent to-[#8aa89a]"></div>
            </div>
          </div>

          {/* Connected Profiles Layout */}
          <div className="flex flex-col items-center gap-8 mb-14 w-full relative">
            {/* Top Row: Main Proponent / Developer */}
            <div className="relative z-10 w-full flex justify-center">
               <div className="absolute top-[76px] left-1/2 w-[1px] h-12 bg-[#dde8e1] -translate-x-1/2 -z-10"></div>
               <ProfilePlaceholder name="John Doe" role="Proponent / Developer" />
            </div>
            
            {/* Bottom Row: 4 Profiles in a single horizontal row */}
            <div className="flex items-start justify-center gap-4 md:gap-8 w-full relative pt-2">
              {/* Subtle connecting horizontal line behind bottom profiles */}
              <div className="absolute top-[38px] left-[5%] right-[5%] h-[1px] bg-gradient-to-r from-transparent via-[#dde8e1] to-transparent -z-10"></div>
              
              <ProfilePlaceholder name="Jane Smith" role="Proponent" />
              <ProfilePlaceholder name="Mark Wilson" role="Proponent" />
              
              {/* Small vertical divider to separate proponents and developers visually */}
              <div className="w-[1px] h-16 bg-gradient-to-b from-transparent via-[#dde8e1] to-transparent shrink-0 mx-1 hidden md:block"></div>
              
              <ProfilePlaceholder name="Alex Johnson" role="Developer" />
              <ProfilePlaceholder name="Sarah Davis" role="Developer" />
            </div>
          </div>

          {/* Floating Elegant Text Block */}
          <div className="relative w-full max-w-[540px]">
            <div className="absolute -top-6 -left-6 text-[#1a7a41]/10 text-[80px] font-serif leading-none opacity-60 select-none">"</div>
            <p className="text-[14.5px] leading-relaxed text-[#4a6356] font-medium text-center relative z-10 px-6">
              FCATS is a comprehensive platform designed to streamline and secure the fee collection 
              processes within the institution. Built with a focus on transparency, efficiency, and 
              accountability, this system enables student organizations and administrators to seamlessly 
              track transactions, generate reports, and manage financial records in real-time.
            </p>
            <div className="absolute -bottom-10 -right-6 text-[#1a7a41]/10 text-[80px] font-serif leading-none opacity-60 select-none">"</div>
          </div>

        </div>
      </div>
    </div>
  );
};

const Sidebar = ({ role, setRole, currentPage, setCurrentPage }: any) => {
  const [menuOpen, setMenuOpen] = useState(false);

  const adminNav = [
    { section: 'System Config', items: [
      { id: 'a-colleges', label: 'Manage Colleges', icon: Building2 },
      { id: 'a-departments', label: 'Manage Departments', icon: Building },
      { id: 'a-programs', label: 'Manage Programs', icon: Layers },
      { id: 'a-ay', label: 'Academic Years', icon: Calendar },
      { id: 'a-organizations', label: 'Manage Organizations', icon: Users },
    ]},
    { section: 'Students', items: [
      { id: 'a-students', label: 'Enrolled Students', icon: Users },
    ]},
    { section: 'System', items: [
      { id: 'a-users', label: 'User Management', icon: Settings },
      { id: 'a-audit', label: 'Audit Logs', icon: FileClock },
    ]}
  ];

  const orgNav = [
    { section: 'Overview', items: [
      { id: 'o-dashboard', label: 'Dashboard', icon: LayoutDashboard },
    ]},
    { section: 'Operations', items: [
      { id: 'o-students', label: 'Enrolled Students', icon: Users },
      { id: 'o-pos', label: 'Create Transaction', icon: CreditCard },
      { id: 'o-void', label: 'Void Requests', icon: XCircle, badge: 3 },
      { id: 'o-remittance', label: 'Remittance', icon: FileText },
    ]},
    { section: 'Management', items: [
      { id: 'o-feeprofiles', label: 'Fee Profiles', icon: Layers },
      { id: 'o-users', label: 'User Management', icon: Settings },
      { id: 'o-documentation', label: 'Documentation', icon: BookOpen },
      { id: 'o-audit', label: 'Audit Logs', icon: FileClock },
    ]}
  ];

  const currentNav = role === 'admin' ? adminNav : orgNav;

  return (
    <div className="w-[260px] min-h-screen bg-[#0d4a1e] flex flex-col shrink-0 shadow-xl z-20">
      <div className="px-6 py-5 flex items-center gap-3.5 border-b border-white/10 shrink-0">
        <div className="w-9 h-9 bg-[#d4a42a] rounded-xl flex items-center justify-center shrink-0 shadow-inner">
          <Layers className="w-5 h-5 text-[#0d4a1e]" />
        </div>
        <div>
          <strong className="block text-[15px] font-bold text-white leading-tight">
            {role === 'admin' ? 'FCATS' : 'COE Council'}
          </strong>
          <span className="text-[11px] text-[#b7dfc7] font-medium leading-tight">
            {role === 'admin' ? 'Admin Panel' : 'Organization Panel'}
          </span>
        </div>
      </div>

      <div className="flex-1 overflow-y-auto overflow-x-hidden py-4 px-3 scrollbar-thin">
        {currentNav.map((sec, i) => (
          <div key={i} className="mb-4">
            <div className="text-[10px] font-bold tracking-widest uppercase text-[#8aa89a] px-3 mb-2">{sec.section}</div>
            <div className="space-y-0.5">
              {sec.items.map((item: any) => (
                <button
                  key={item.id} onClick={() => setCurrentPage(item.id)}
                  className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13.5px] font-medium transition-all ${
                    currentPage === item.id ? 'bg-[#1a7a41] text-white shadow-sm' : 'text-[#b7dfc7] hover:bg-white/5 hover:text-white'
                  }`}
                >
                  <item.icon className={`w-[16px] h-[16px] shrink-0 ${currentPage === item.id ? 'opacity-100' : 'opacity-70'}`} />
                  <span className="truncate">{item.label}</span>
                  {item.badge && (
                    <span className="ml-auto bg-[#d4a42a] text-[#0d4a1e] text-[10px] font-bold px-2 py-0.5 rounded-full min-w-[20px] text-center shadow-sm">
                      {item.badge}
                    </span>
                  )}
                </button>
              ))}
            </div>
          </div>
        ))}
      </div>

      <div className="relative mt-auto p-4 border-t border-white/10 shrink-0 bg-[#0a3816]/30">
        <button 
          onClick={() => setMenuOpen(!menuOpen)}
          className="flex items-center gap-3 w-full p-2 -mx-2 rounded-xl hover:bg-white/5 transition-colors text-left group"
        >
          <div className="w-[36px] h-[36px] bg-[#1a7a41] rounded-full flex items-center justify-center text-[13px] font-bold text-white shrink-0 ring-2 ring-transparent group-hover:ring-[#27a05a] transition-all">
            {role === 'admin' ? 'SA' : 'JD'}
          </div>
          <div className="flex-1 min-w-0">
            <div className="text-[13.5px] font-bold text-white truncate">{role === 'admin' ? 'SSC Admin' : 'Juan dela Cruz'}</div>
            <div className="text-[11px] text-[#b7dfc7] font-medium truncate">{role === 'admin' ? 'Super Administrator' : 'Chairperson'}</div>
          </div>
          {menuOpen ? <ChevronDown className="w-4 h-4 text-white/50 shrink-0" /> : <ChevronUp className="w-4 h-4 text-white/50 shrink-0 group-hover:text-white transition-colors" />}
        </button>

        {menuOpen && (
          <div className="absolute bottom-full left-4 right-4 mb-3 bg-white rounded-xl shadow-2xl overflow-hidden border border-[#dde8e1] py-1.5 z-50 transform origin-bottom animate-in slide-in-from-bottom-2 fade-in duration-200">
            <div className="px-3.5 py-2.5 border-b border-[#eaf0ec] bg-[#f8fbf9]">
              <div className="text-[10px] font-bold text-[#8aa89a] uppercase tracking-wider">Switch Role</div>
            </div>
            <div className="p-1.5 space-y-1">
              <button 
                onClick={() => { setRole('admin'); setCurrentPage('a-colleges'); setMenuOpen(false); }}
                className={`w-full text-left px-3 py-2.5 text-[13px] font-semibold rounded-lg flex items-center gap-3 ${role === 'admin' ? 'bg-[#e6f4ec] text-[#0d4a1e]' : 'text-[#4a6356] hover:bg-[#f0f3f1] hover:text-[#0f1f17]'}`}
              >
                <div className={`w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 ${role === 'admin' ? 'bg-[#1a7a41] text-white' : 'bg-[#dde8e1] text-[#4a6356]'}`}>SA</div>
                <span>Admin (SSC)</span>
                {role === 'admin' && <CheckCircle2 className="w-4 h-4 text-[#1a7a41] ml-auto" />}
              </button>
              <button 
                onClick={() => { setRole('org'); setCurrentPage('o-dashboard'); setMenuOpen(false); }}
                className={`w-full text-left px-3 py-2.5 text-[13px] font-semibold rounded-lg flex items-center gap-3 ${role === 'org' ? 'bg-[#e6f4ec] text-[#0d4a1e]' : 'text-[#4a6356] hover:bg-[#f0f3f1] hover:text-[#0f1f17]'}`}
              >
                <div className={`w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 ${role === 'org' ? 'bg-[#1a7a41] text-white' : 'bg-[#dde8e1] text-[#4a6356]'}`}>JD</div>
                <span>Organization</span>
                {role === 'org' && <CheckCircle2 className="w-4 h-4 text-[#1a7a41] ml-auto" />}
              </button>
            </div>
            <div className="h-[1px] bg-[#eaf0ec] my-0.5"></div>
            <div className="p-1.5">
              <button onClick={() => { setRole('login'); setMenuOpen(false); }} className="w-full text-left px-3 py-2 text-[13px] font-semibold text-red-600 hover:bg-red-50 rounded-lg flex items-center gap-2.5 transition-colors">
                <LogOut className="w-4 h-4" /> Sign Out
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

const Topbar = ({ role, currentPage }: { role: string, currentPage: string }) => {
  const [notificationsOpen, setNotificationsOpen] = useState(false);

  const getPageTitle = () => {
    const titles: Record<string, string> = {
      'a-colleges': 'Manage Colleges', 'a-departments': 'Manage Departments', 'a-programs': 'Manage Programs',
      'a-ay': 'Academic Years', 'a-organizations': 'Manage Organizations', 'a-students': 'Enrolled Students',
      'a-users': 'User Management', 'a-audit': 'Audit Logs', 'o-dashboard': 'Dashboard',
      'o-students': 'Enrolled Students', 'o-pos': 'Create Transaction', 'o-void': 'Void Requests',
      'o-remittance': 'Remittance', 'o-feeprofiles': 'Fee Profiles', 'o-users': 'User Management',
      'o-documentation': 'Documentation', 'o-audit': 'Audit Logs'
    };
    return titles[currentPage] || 'System View';
  };

  const notifications = [
    { id: 1, type: 'void', title: 'New Void Request', text: 'Jane Doe requested a void for OR COE-00420.', time: '10 mins ago', unread: true, icon: XCircle, color: 'text-red-600', bg: 'bg-red-50' },
    { id: 2, type: 'remittance', title: 'Remittance Approved', text: 'Admin approved your remittance for Oct 14.', time: '1 hour ago', unread: true, icon: FileText, color: 'text-[#1a7a41]', bg: 'bg-[#e6f4ec]' },
    { id: 3, type: 'system', title: 'System Maintenance', text: 'FCATS will undergo maintenance at 12:00 AM.', time: '5 hours ago', unread: false, icon: Settings, color: 'text-[#4a6356]', bg: 'bg-[#f0f3f1]' },
    { id: 4, type: 'transaction', title: 'High Value Transaction', text: 'A collection of ₱ 5,000.00 was recorded.', time: 'Yesterday', unread: false, icon: CreditCard, color: 'text-[#d4a42a]', bg: 'bg-[#fdf7e3]' },
  ];

  return (
    <div className="h-[64px] bg-white border-b border-[#dde8e1] flex items-center px-8 shrink-0 shadow-[0_1px_2px_rgba(0,0,0,0.02)] z-10 relative">
      <div className="flex items-center gap-2 text-[13px] text-[#8aa89a] flex-1 font-medium">
        <span className="cursor-pointer hover:text-[#4a6356] transition-colors">FCATS</span>
        <span className="text-[11px]">/</span>
        <span className="text-[#0f1f17] font-bold">{getPageTitle()}</span>
      </div>
      <div className="flex items-center gap-3 relative">
        <div className="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#f8fbf9] border border-[#dde8e1] text-[12px] font-bold text-[#4a6356]">
          <div className="w-2 h-2 bg-[#16a34a] rounded-full shadow-[0_0_0_2px_rgba(22,163,74,0.2)]"></div>
          2024–2025 2nd Sem
        </div>
        
        <div className="relative">
          <button 
            onClick={() => setNotificationsOpen(!notificationsOpen)}
            className={`w-[36px] h-[36px] rounded-xl border flex items-center justify-center transition-all relative ${notificationsOpen ? 'border-[#1a7a41] text-[#1a7a41] bg-[#e6f4ec]' : 'border-[#dde8e1] bg-white text-[#4a6356] hover:text-[#1a7a41] hover:border-[#1a7a41] hover:bg-[#f0f3f1]'}`}
          >
            <Bell className="w-4 h-4" />
            <div className="absolute top-2 right-2 w-[8px] h-[8px] bg-red-500 rounded-full border-2 border-white"></div>
          </button>

          {notificationsOpen && (
            <>
              {/* Invisible overlay to close on click outside */}
              <div className="fixed inset-0 z-40" onClick={() => setNotificationsOpen(false)}></div>
              
              <div className="absolute right-0 top-[calc(100%+8px)] w-[360px] bg-white rounded-2xl shadow-2xl border border-[#dde8e1] z-50 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
                <div className="flex items-center justify-between px-5 py-4 border-b border-[#eaf0ec] bg-[#f8fbf9]">
                  <h3 className="text-[16px] font-bold text-[#0f1f17]">Notifications</h3>
                  <button className="text-[12px] font-bold text-[#1a7a41] hover:text-[#27a05a]">Mark all as read</button>
                </div>
                
                <div className="max-h-[380px] overflow-y-auto scrollbar-thin">
                  {notifications.map(notif => (
                    <div 
                      key={notif.id} 
                      className={`p-4 border-b border-[#eaf0ec] hover:bg-[#f8fbf9] transition-colors cursor-pointer flex gap-4 last:border-b-0 ${notif.unread ? 'bg-white' : 'bg-[#fcfcfc] opacity-80'}`}
                    >
                      <div className="relative shrink-0">
                        <div className={`w-11 h-11 rounded-full flex items-center justify-center ${notif.bg} ${notif.color}`}>
                          <notif.icon className="w-5 h-5" />
                        </div>
                        {notif.unread && (
                          <div className="absolute -bottom-1 -right-1 w-4 h-4 bg-[#1a7a41] rounded-full border-2 border-white flex items-center justify-center">
                             <div className="w-1.5 h-1.5 bg-white rounded-full"></div>
                          </div>
                        )}
                      </div>
                      
                      <div className="flex-1 min-w-0">
                        <div className="flex justify-between items-start mb-0.5">
                          <span className={`text-[14px] font-bold truncate pr-2 ${notif.unread ? 'text-[#0f1f17]' : 'text-[#4a6356]'}`}>
                            {notif.title}
                          </span>
                          <span className="text-[11px] font-bold text-[#8aa89a] whitespace-nowrap shrink-0 mt-0.5">
                            {notif.time}
                          </span>
                        </div>
                        <p className={`text-[13px] line-clamp-2 leading-snug ${notif.unread ? 'text-[#4a6356] font-medium' : 'text-[#8aa89a]'}`}>
                          {notif.text}
                        </p>
                        {notif.type === 'void' && notif.unread && (
                           <div className="mt-2.5 flex gap-2">
                              <button className="px-3 py-1.5 bg-[#1a7a41] text-white text-[12px] font-bold rounded-lg shadow-sm hover:bg-[#27a05a] transition-colors">Review</button>
                              <button className="px-3 py-1.5 bg-white border border-[#dde8e1] text-[#4a6356] text-[12px] font-bold rounded-lg hover:border-[#1a7a41] hover:text-[#1a7a41] transition-colors">Dismiss</button>
                           </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
                
                <div className="p-3 border-t border-[#eaf0ec] bg-white text-center">
                  <button className="text-[13px] font-bold text-[#1a7a41] hover:text-[#27a05a] w-full py-2 hover:bg-[#f0f3f1] rounded-lg transition-colors">
                    View All Notifications
                  </button>
                </div>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
};

// ------------------------------------------------------------------
// DYNAMIC PAGE CONTENT ROUTER
// ------------------------------------------------------------------
const PageContent = ({ currentPage, role }: { currentPage: string, role: string }) => {
  switch (currentPage) {
    case 'a-colleges': return <AdminCollegesPage />;
    case 'o-dashboard': return <OrgDashboardPage />;
    case 'o-pos': return <OrgPOSPage />;
    case 'o-documentation': return <OrgDocsPage />;
    case 'o-void': return <OrgVoidPage />;
    default: return <GenericDataTablePage pageId={currentPage} />;
  }
};

// ------------------------------------------------------------------
// SPECIFIC PAGES
// ------------------------------------------------------------------

const OrgPOSPage = () => {
  const [step, setStep] = useState(1);

  const getStepClass = (s: number) => {
    if (step > s) return "text-[#16a34a] bg-[#16a34a] text-white"; // completed
    if (step === s) return "text-white bg-[#1a7a41]"; // active
    return "text-[#8aa89a] bg-[#f0f3f1] border border-[#dde8e1]"; // pending
  };

  const getStepTextClass = (s: number) => {
    if (step > s) return "text-[#16a34a]";
    if (step === s) return "text-[#0f1f17]";
    return "text-[#8aa89a]";
  };

  const getLineClass = (s: number) => {
    if (step > s) return "bg-[#16a34a]";
    return "bg-[#dde8e1]";
  };

  return (
    <div className="max-w-6xl mx-auto pb-10">
      <PageHeader 
        title="Create Transaction" 
        subtitle="Point of Sale · One receipt per fee item (FR-0017)" 
      />

      <div className="flex items-center gap-2 mb-6 bg-white p-4 rounded-2xl border border-[#dde8e1] shadow-sm overflow-x-auto">
        {[
          { num: 1, label: 'Find Student' },
          { num: 2, label: 'Select Fee' },
          { num: 3, label: 'Payment' },
          { num: 4, label: 'Confirm' }
        ].map((item, index) => (
          <React.Fragment key={item.num}>
            <div className={`flex items-center gap-2.5 text-[13px] font-bold ${getStepTextClass(item.num)}`}>
              <div className={`w-6 h-6 rounded-full flex items-center justify-center text-[11px] ${getStepClass(item.num)}`}>
                {step > item.num ? <Check className="w-3 h-3" /> : item.num}
              </div>
              {item.label}
            </div>
            {index < 3 && <div className={`flex-1 h-[2px] mx-2 min-w-[20px] ${getLineClass(item.num)}`}></div>}
          </React.Fragment>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-6 items-start">
        <div className="space-y-6">
          {step === 1 && (
            <div className="bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden animate-in fade-in slide-in-from-bottom-2 duration-300">
              <div className="px-5 py-4 border-b border-[#eaf0ec] bg-[#f8fbf9]">
                <h3 className="text-[14px] font-bold text-[#0f1f17]">Step 1 — Find Student</h3>
              </div>
              <div className="p-6">
                <label className="text-[13px] font-semibold text-[#4a6356] block mb-2">Search Enrolled Student</label>
                <div className="relative mb-5">
                  <Search className="w-5 h-5 text-[#8aa89a] absolute left-4 top-1/2 -translate-y-1/2" />
                  <input type="text" placeholder="Enter Student ID or Name" className="w-full pl-12 pr-4 py-3 border-2 border-[#dde8e1] rounded-xl text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                </div>
                <div className="space-y-3">
                  <div className="p-4 rounded-xl border-2 border-[#1a7a41] bg-[#e6f4ec] flex items-center justify-between cursor-pointer">
                    <div className="flex items-center gap-3.5">
                      <div className="w-10 h-10 rounded-full bg-[#1a7a41] text-white flex items-center justify-center font-bold text-[13px]">MS</div>
                      <div>
                        <div className="text-[14px] font-bold text-[#0f1f17]">Maria Santos</div>
                        <div className="text-[12px] font-medium text-[#4a6356]">2024-0001 · BSCE · COE</div>
                      </div>
                    </div>
                    <div className="text-[#1a7a41]"><CheckCircle2 className="w-5 h-5" /></div>
                  </div>
                  <div className="p-4 rounded-xl border-2 border-[#dde8e1] hover:border-[#1a7a41]/50 bg-white flex items-center justify-between cursor-pointer transition-colors">
                    <div className="flex items-center gap-3.5 opacity-70">
                      <div className="w-10 h-10 rounded-full bg-[#f0f3f1] text-[#4a6356] flex items-center justify-center font-bold text-[13px]">PP</div>
                      <div>
                        <div className="text-[14px] font-bold text-[#0f1f17]">Pedro Penduko</div>
                        <div className="text-[12px] font-medium text-[#4a6356]">2024-0142 · BSME · COE</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {step === 2 && (
            <div className="bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden animate-in fade-in slide-in-from-bottom-2 duration-300">
              <div className="flex items-center justify-between px-5 py-4 border-b border-[#eaf0ec] bg-[#f8fbf9]">
                <h3 className="text-[14px] font-bold text-[#0f1f17]">Step 2 — Fee Selection</h3>
                <button onClick={() => setStep(1)} className="text-[12px] font-bold text-[#1a7a41] hover:text-[#27a05a]">Change Student</button>
              </div>
              <div className="p-6">
                <div className="grid grid-cols-2 gap-4 mb-6">
                  <button className="p-4 rounded-xl border-2 border-[#1a7a41] bg-[#e6f4ec] text-left transition-colors">
                    <div className="text-[13.5px] font-bold text-[#0f1f17]">Membership Fee</div>
                    <div className="text-[11.5px] font-medium text-[#4a6356] mt-1">Uses a predefined fee profile</div>
                  </button>
                  <button className="p-4 rounded-xl border-2 border-[#dde8e1] bg-white hover:border-[#1a7a41]/50 text-left transition-colors">
                    <div className="text-[13.5px] font-bold text-[#0f1f17]">Other / Fine</div>
                    <div className="text-[11.5px] font-medium text-[#8aa89a] mt-1">Manual ad-hoc entry</div>
                  </button>
                </div>

                <div className="mb-3">
                  <label className="text-[13px] font-semibold text-[#4a6356]">Student Category <span className="text-red-500">*</span></label>
                </div>
                <div className="grid grid-cols-2 gap-4 mb-6">
                  <div className="p-3.5 rounded-xl border-2 border-[#1a7a41] bg-[#e6f4ec] cursor-pointer">
                    <div className="text-[13px] font-bold text-[#0f1f17]">Regular Member</div>
                    <div className="text-[12px] font-medium text-[#4a6356] mt-0.5">₱ 150.00 — standard rate</div>
                  </div>
                  <div className="p-3.5 rounded-xl border-2 border-[#dde8e1] hover:border-[#1a7a41]/50 cursor-pointer transition-colors opacity-60">
                    <div className="text-[13px] font-bold text-[#0f1f17]">Irregular / Affiliate</div>
                    <div className="text-[12px] font-medium text-[#8aa89a] mt-0.5">Requires manual rate selection</div>
                  </div>
                </div>

                <div>
                  <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Amount Due <span className="text-red-500">*</span></label>
                  <input 
                    type="text" 
                    value="₱ 150.00" 
                    disabled
                    className="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-[#f0f3f1] text-[16px] font-bold font-mono text-[#0f1f17] outline-none cursor-not-allowed"
                  />
                  <p className="text-[12px] text-[#8aa89a] mt-2 font-medium">Amount is pre-filled from the fee profile and cannot be manually altered.</p>
                </div>
              </div>
            </div>
          )}

          {step === 3 && (
            <div className="bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden animate-in fade-in slide-in-from-bottom-2 duration-300">
              <div className="flex items-center justify-between px-5 py-4 border-b border-[#eaf0ec] bg-[#f8fbf9]">
                <h3 className="text-[14px] font-bold text-[#0f1f17]">Step 3 — Payment</h3>
                <button onClick={() => setStep(2)} className="text-[12px] font-bold text-[#1a7a41] hover:text-[#27a05a]">Back to Fees</button>
              </div>
              <div className="p-6 space-y-6">
                <div>
                  <label className="text-[13px] font-semibold text-[#4a6356] block mb-2">Amount Tendered <span className="text-red-500">*</span></label>
                  <div className="relative">
                     <span className="absolute left-4 top-1/2 -translate-y-1/2 text-[#4a6356] font-bold text-[16px]">₱</span>
                     <input type="text" defaultValue="500.00" className="w-full pl-9 pr-4 py-3 border-2 border-[#1a7a41] rounded-xl text-[18px] font-bold font-mono text-[#0f1f17] outline-none focus:ring-4 focus:ring-[#1a7a41]/20 transition-all bg-white" />
                  </div>
                </div>
                <div className="grid grid-cols-4 gap-3">
                   <button className="py-2.5 rounded-lg border border-[#dde8e1] bg-[#f8fbf9] text-[13.5px] font-bold text-[#0f1f17] hover:border-[#1a7a41] hover:text-[#1a7a41] transition-colors">Exact</button>
                   <button className="py-2.5 rounded-lg border border-[#dde8e1] bg-[#f8fbf9] text-[13.5px] font-bold text-[#0f1f17] hover:border-[#1a7a41] hover:text-[#1a7a41] transition-colors">₱ 200</button>
                   <button className="py-2.5 rounded-lg border-2 border-[#1a7a41] bg-[#e6f4ec] text-[13.5px] font-bold text-[#1a7a41] transition-colors">₱ 500</button>
                   <button className="py-2.5 rounded-lg border border-[#dde8e1] bg-[#f8fbf9] text-[13.5px] font-bold text-[#0f1f17] hover:border-[#1a7a41] hover:text-[#1a7a41] transition-colors">₱ 1000</button>
                </div>
                <div className="p-5 rounded-xl border border-[#dde8e1] bg-[#f8fbf9] flex items-center justify-between">
                   <span className="text-[15px] font-bold text-[#4a6356]">Change Due</span>
                   <span className="text-[24px] font-extrabold font-mono text-[#1a7a41]">₱ 350.00</span>
                </div>
              </div>
            </div>
          )}

          {step === 4 && (
            <div className="bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden p-10 text-center animate-in fade-in zoom-in-95 duration-400">
              <div className="w-20 h-20 rounded-full bg-[#1a7a41] text-white flex items-center justify-center mx-auto mb-6 shadow-xl shadow-[#1a7a41]/30">
                <Check className="w-10 h-10" />
              </div>
              <h3 className="text-[24px] font-extrabold text-[#0f1f17] mb-2">Transaction Successful</h3>
              <p className="text-[14px] text-[#4a6356] mb-8">Official Receipt <span className="font-mono font-bold text-[#1a7a41]">COE-00422</span> has been recorded.</p>
              
              <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
                 <Button variant="outline" icon={FileText} className="w-full sm:w-auto px-8 py-3 text-[14px]">Print Receipt</Button>
                 <Button variant="primary" icon={Plus} onClick={() => setStep(1)} className="w-full sm:w-auto px-8 py-3 text-[14px]">New Transaction</Button>
              </div>
            </div>
          )}
        </div>

        {/* SUMMARY SIDEBAR */}
        <div className="bg-white rounded-2xl border border-[#dde8e1] shadow-sm p-5 sticky top-6">
          <h3 className="text-[15px] font-bold text-[#0f1f17] mb-5">Transaction Summary</h3>
          
          <div className="space-y-3.5 mb-6">
            <div className="flex justify-between items-start">
              <span className="text-[12.5px] font-bold text-[#8aa89a] uppercase tracking-wider w-[100px]">Student</span>
              <span className={`text-[13.5px] font-bold text-right ${step >= 1 ? 'text-[#0f1f17]' : 'text-[#8aa89a] italic'}`}>
                {step >= 1 ? 'Maria Santos' : '—'}
              </span>
            </div>
            <div className="flex justify-between items-start">
              <span className="text-[12.5px] font-bold text-[#8aa89a] uppercase tracking-wider w-[100px]">Student No.</span>
              {step >= 1 ? (
                <span className="text-[13px] font-mono font-bold text-[#1a7a41] bg-[#e6f4ec] px-1.5 rounded text-right">2024-0001</span>
              ) : (
                <span className="text-[13.5px] font-bold text-[#8aa89a] italic text-right">—</span>
              )}
            </div>
            <div className="flex justify-between items-start">
              <span className="text-[12.5px] font-bold text-[#8aa89a] uppercase tracking-wider w-[100px]">Semester</span>
              <span className="text-[13.5px] font-bold text-[#0f1f17] text-right">2024–25 2nd Sem</span>
            </div>
            <div className="flex justify-between items-start">
              <span className="text-[12.5px] font-bold text-[#8aa89a] uppercase tracking-wider w-[100px]">Type</span>
              {step >= 2 ? (
                <span className="text-[11.5px] font-bold text-[#1d4ed8] bg-[#dbeafe] px-2 py-0.5 rounded text-right">Membership Fee</span>
              ) : (
                <span className="text-[13.5px] font-bold text-[#8aa89a] italic text-right">—</span>
              )}
            </div>
            <div className="flex justify-between items-start">
              <span className="text-[12.5px] font-bold text-[#8aa89a] uppercase tracking-wider w-[100px]">Category</span>
              <span className={`text-[13.5px] font-bold text-right ${step >= 2 ? 'text-[#0f1f17]' : 'text-[#8aa89a] italic'}`}>
                {step >= 2 ? 'Regular' : '—'}
              </span>
            </div>
          </div>

          <div className="border-t border-b border-[#eaf0ec] py-4 mb-5">
            <div className="flex justify-between items-center mb-1">
              <span className="text-[13.5px] font-semibold text-[#4a6356]">Fee Amount</span>
              <span className={`text-[14px] font-bold ${step >= 2 ? 'text-[#0f1f17]' : 'text-[#8aa89a]'}`}>
                {step >= 2 ? '₱ 150.00' : '₱ 0.00'}
              </span>
            </div>
            <div className="flex justify-between items-center mt-3 pt-3 border-t border-[#eaf0ec]">
              <span className="text-[14.5px] font-bold text-[#0f1f17]">Total Due</span>
              <span className={`text-[18px] font-extrabold ${step >= 2 ? 'text-[#1a7a41]' : 'text-[#8aa89a]'}`}>
                {step >= 2 ? '₱ 150.00' : '₱ 0.00'}
              </span>
            </div>
          </div>

          <div className="text-[13px] font-medium text-[#4a6356] mb-5 bg-[#f8fbf9] p-3 rounded-lg border border-[#eaf0ec]">
            <strong>Next OR No.:</strong> <span className="font-mono text-[#1a7a41] font-bold ml-1">COE-00422</span>
          </div>

          {step < 4 && (
            <div className="flex gap-3">
              {step > 1 && (
                <Button variant="outline" onClick={() => setStep(step - 1)} className="px-3">
                  <ArrowLeft className="w-4 h-4" />
                </Button>
              )}
              <Button 
                variant="primary" 
                onClick={() => setStep(step + 1)} 
                className="flex-1 justify-center"
              >
                {step === 1 ? 'Proceed to Fees' : step === 2 ? 'Proceed to Payment' : 'Complete Transaction'} 
                {step < 3 && <ArrowRight className="w-4 h-4 ml-1" />}
              </Button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

const OrgVoidPage = () => {
  const [isCreating, setIsCreating] = useState(false);

  return (
    <div className="max-w-6xl mx-auto pb-10 relative">
      <PageHeader 
        title="Void Requests" 
        subtitle="Two-step void workflow (FR-0019) · Requires authorization"
        actions={[
          { label: 'New Void Request', icon: Plus, variant: 'primary', onClick: () => setIsCreating(true) }
        ]}
      />

      <div className="bg-[#fdf7e3] border border-[#fde68a] rounded-xl p-4 flex items-start gap-3 mb-6 shadow-sm">
        <AlertCircle className="w-5 h-5 text-[#d97706] shrink-0 mt-0.5" />
        <div>
          <div className="text-[13.5px] font-bold text-[#92400e]">3 pending void requests require your attention.</div>
          <div className="text-[12.5px] text-[#92400e] opacity-80 font-medium mt-0.5">Voided OR numbers are retained for audit purposes and never reused. Approved voids will deduct from your total collection amount.</div>
        </div>
      </div>

      <div className="bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-5 border-b border-[#eaf0ec]">
          <div>
            <h3 className="text-[15px] font-bold text-[#0f1f17]">Data Records</h3>
            <p className="text-[12.5px] text-[#8aa89a] font-medium mt-0.5">2 total records</p>
          </div>
          <div className="flex flex-wrap items-center gap-2.5">
            <div className="relative w-full md:w-[240px]">
              <Search className="w-4 h-4 text-[#8aa89a] absolute left-3 top-1/2 -translate-y-1/2" />
              <input 
                type="text" 
                placeholder="Search records..." 
                className="w-full pl-9 pr-3 py-2 border-2 border-[#dde8e1] rounded-xl text-[13px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] hover:border-[#b7dfc7] transition-colors"
              />
            </div>
            <button className="px-3 py-2 border-2 border-[#dde8e1] rounded-xl text-[#4a6356] hover:border-[#1a7a41] hover:text-[#1a7a41] transition-colors flex items-center gap-2">
              <Filter className="w-4 h-4" /> <span className="text-[13px] font-bold">Filter</span>
            </button>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse min-w-[700px]">
            <thead>
              <tr className="bg-[#f8fbf9] border-b border-[#dde8e1]">
                <th className="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest whitespace-nowrap">Request ID</th>
                <th className="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest whitespace-nowrap">OR Number</th>
                <th className="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest whitespace-nowrap">Amount</th>
                <th className="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest whitespace-nowrap">Requested By</th>
                <th className="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest whitespace-nowrap">Status</th>
                <th className="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest whitespace-nowrap text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {[
                { c1: 'VR-2024-012', c2: 'COE-00341', c3: '₱ 150.00', c4: 'Jane Doe', c5: 'Pending' },
                { c1: 'VR-2024-011', c2: 'COE-00310', c3: '₱ 300.00', c4: 'Jane Doe', c5: 'Approved' },
              ].map((row, i) => (
                <tr key={i} className="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0 group">
                  <td className="px-6 py-4 align-middle">
                    <span className="font-mono text-[13px] font-bold text-[#1a7a41] bg-[#e6f4ec] px-2 py-1 rounded-md">{row.c1}</span>
                  </td>
                  <td className="px-6 py-4 align-middle text-[14px] font-bold text-[#0f1f17]">{row.c2}</td>
                  <td className="px-6 py-4 align-middle text-[13.5px] font-semibold text-[#4a6356]">{row.c3}</td>
                  <td className="px-6 py-4 align-middle text-[13.5px] font-semibold text-[#4a6356]">{row.c4}</td>
                  <td className="px-6 py-4 align-middle">
                    {row.c5 === 'Approved' ? (
                      <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#dcfce7] text-[#15803d] text-[11.5px] font-bold">
                        <div className="w-1.5 h-1.5 rounded-full bg-[#16a34a]"></div>
                        {row.c5}
                      </div>
                    ) : (
                      <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#fef9c3] text-[#ca8a04] text-[11.5px] font-bold">
                        <div className="w-1.5 h-1.5 rounded-full bg-[#eab308]"></div>
                        {row.c5}
                      </div>
                    )}
                  </td>
                  <td className="px-6 py-4 align-middle text-right">
                    <div className="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                      <IconButton icon={Eye} title="View Details" />
                      <IconButton icon={Trash2} variant="danger" title="Delete Record" />
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        
        <div className="px-6 py-4 border-t border-[#eaf0ec] flex justify-between items-center bg-[#f8fbf9]">
          <span className="text-[12.5px] font-medium text-[#8aa89a]">Showing 2 entries</span>
          <div className="flex items-center gap-1.5">
            <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-[#dde8e1] bg-white text-[#4a6356] shadow-sm opacity-50 cursor-not-allowed">‹</button>
            <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-[#1a7a41] bg-[#1a7a41] text-white text-[13px] font-bold shadow-sm">1</button>
            <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-[#dde8e1] bg-white text-[#4a6356] shadow-sm opacity-50 cursor-not-allowed">›</button>
          </div>
        </div>
      </div>

      {isCreating && (
        <div className="fixed top-[64px] left-[260px] right-0 bottom-0 z-40 flex items-center justify-center p-4 sm:p-6">
          <div className="absolute inset-0 bg-black/50 backdrop-blur-[2px] transition-opacity" onClick={() => setIsCreating(false)}></div>
          
          <div className="relative bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] flex flex-col shadow-2xl z-10 animate-in fade-in zoom-in-95 duration-200">
            <div className="flex items-center justify-between px-6 py-4 border-b border-[#eaf0ec] shrink-0">
              <div>
                <h2 className="text-[18px] font-bold text-[#0f1f17]">New Void Request</h2>
                <p className="text-[13px] text-[#4a6356] mt-0.5 font-medium">Submit a transaction for cancellation approval.</p>
              </div>
              <button 
                onClick={() => setIsCreating(false)} 
                className="text-[#8aa89a] hover:bg-[#f0f3f1] hover:text-[#0f1f17] p-2 rounded-xl transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
            
            <div className="p-6 overflow-y-auto scrollbar-thin">
              <div className="mb-6">
                <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Search Receipt (OR Number) <span className="text-red-500">*</span></label>
                <div className="flex gap-3">
                  <input 
                    type="text" 
                    defaultValue="COE-00421"
                    className="flex-1 px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-[#f8fbf9] text-[14px] font-bold font-mono text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors"
                  />
                  <Button variant="outline" icon={Search}>Find Receipt</Button>
                </div>
              </div>

              <div className="bg-[#e6f4ec] border border-[#1a7a41]/30 rounded-xl p-4 mb-6">
                <div className="flex justify-between items-start mb-3 border-b border-[#1a7a41]/10 pb-3">
                  <div>
                    <div className="text-[12px] font-bold text-[#1a7a41] uppercase tracking-widest mb-0.5">Receipt Found</div>
                    <div className="text-[15px] font-bold text-[#0f1f17]">Maria Santos</div>
                    <div className="text-[13px] font-medium text-[#4a6356]">Membership Fee • Regular</div>
                  </div>
                  <div className="text-right">
                    <div className="text-[18px] font-extrabold text-[#1a7a41]">₱ 150.00</div>
                    <div className="text-[12px] font-medium text-[#4a6356]">Issued Today, 09:12 AM</div>
                  </div>
                </div>
                <div className="text-[12.5px] text-[#4a6356] flex items-center gap-1.5 font-medium">
                  <CheckCircle2 className="w-4 h-4 text-[#1a7a41]" />
                  Eligible for voiding (Issued within active semester)
                </div>
              </div>

              <div className="mb-6">
                <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Reason for Voiding <span className="text-red-500">*</span></label>
                <select className="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                  <option>Error in Amount</option>
                  <option>Wrong Student Selected</option>
                  <option>Duplicate Transaction</option>
                  <option>Other / Specified in remarks</option>
                </select>
              </div>

              <div className="mb-6">
                <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Additional Remarks</label>
                <textarea 
                  rows={3}
                  placeholder="Provide more context for the approver..."
                  className="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-[#f8fbf9] text-[14px] text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors resize-none"
                ></textarea>
              </div>

              <div className="mb-2">
                <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Supporting Document (Optional)</label>
                <div className="border-2 border-dashed border-[#dde8e1] hover:border-[#1a7a41] bg-[#f8fbf9] hover:bg-[#e6f4ec] rounded-xl p-8 flex flex-col items-center justify-center text-center cursor-pointer transition-all group">
                  <div className="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm mb-3 group-hover:scale-110 transition-transform">
                    <UploadCloud className="w-6 h-6 text-[#1a7a41]" />
                  </div>
                  <div className="text-[14px] font-bold text-[#0f1f17] mb-1">Click to upload or drag and drop</div>
                  <div className="text-[12.5px] text-[#8aa89a] font-medium">PDF, JPG, or PNG (Max. 5MB)</div>
                </div>
              </div>
            </div>

            <div className="px-6 py-4 border-t border-[#eaf0ec] bg-[#f8fbf9] flex justify-end gap-3 shrink-0 rounded-b-2xl">
              <Button variant="outline" onClick={() => setIsCreating(false)}>Cancel</Button>
              <Button variant="primary">Submit for Approval</Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

const OrgDocsPage = () => {
  return (
    <div className="max-w-4xl mx-auto pb-10">
      <div className="mb-8 text-center">
        <div className="w-16 h-16 bg-[#e6f4ec] text-[#1a7a41] rounded-2xl flex items-center justify-center mx-auto mb-4">
          <BookOpen className="w-8 h-8" />
        </div>
        <h2 className="text-[24px] font-bold text-[#0f1f17]">System Documentation</h2>
        <p className="text-[14px] text-[#4a6356] mt-2 font-medium">Learn how to use FCATS for your organization.</p>
      </div>
      
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
        {[
          { title: 'Getting Started', desc: 'Basic orientation and initial setup for new officers.' },
          { title: 'Processing Payments', desc: 'Step-by-step guide to the POS and printing ORs.' },
          { title: 'Voiding Receipts', desc: 'How to request a void and the approval workflow.' },
          { title: 'Remittance Process', desc: 'Generating reports and transferring funds securely.' },
        ].map((doc, i) => (
          <div key={i} className="bg-white p-6 rounded-2xl border border-[#dde8e1] shadow-sm hover:border-[#1a7a41] hover:shadow-md transition-all cursor-pointer group">
            <h3 className="text-[16px] font-bold text-[#0f1f17] group-hover:text-[#1a7a41] transition-colors mb-2">{doc.title}</h3>
            <p className="text-[13.5px] text-[#8aa89a] font-medium">{doc.desc}</p>
          </div>
        ))}
      </div>
    </div>
  );
};

const AdminCollegesPage = () => {
  const [isCreating, setIsCreating] = useState(false);

  return (
    <div className="max-w-6xl mx-auto pb-10 relative">
      <PageHeader 
        title="Manage Colleges" 
        subtitle="Define and manage the university's college units (Level 1)"
        actions={[
          { label: 'Import List', icon: Upload, variant: 'outline' },
          { label: 'Add College', icon: Plus, variant: 'primary', onClick: () => setIsCreating(true) }
        ]}
      />

      <div className="bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-5 border-b border-[#eaf0ec]">
          <div>
            <h3 className="text-[15px] font-bold text-[#0f1f17]">All Colleges</h3>
            <p className="text-[12.5px] text-[#8aa89a] font-medium mt-0.5">8 total records</p>
          </div>
          <div className="flex flex-wrap items-center gap-2.5">
            <div className="relative w-full md:w-[240px]">
              <Search className="w-4 h-4 text-[#8aa89a] absolute left-3 top-1/2 -translate-y-1/2" />
              <input 
                type="text" 
                placeholder="Search colleges..." 
                className="w-full pl-9 pr-3 py-2 border-2 border-[#dde8e1] rounded-xl text-[13px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] hover:border-[#b7dfc7] transition-colors"
              />
            </div>
            <select className="border-2 border-[#dde8e1] rounded-xl py-2 px-3 text-[13px] font-medium text-[#4a6356] outline-none focus:border-[#1a7a41] hover:border-[#b7dfc7] bg-white cursor-pointer transition-colors min-w-[120px]">
              <option>All Status</option>
              <option>Active</option>
              <option>Inactive</option>
            </select>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse min-w-[700px]">
            <thead>
              <tr className="bg-[#f8fbf9] border-b border-[#dde8e1]">
                <th className="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest whitespace-nowrap">Code</th>
                <th className="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest whitespace-nowrap">College Name</th>
                <th className="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest whitespace-nowrap">Departments</th>
                <th className="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest whitespace-nowrap">Programs</th>
                <th className="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest whitespace-nowrap">Status</th>
                <th className="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest whitespace-nowrap text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {[
                { code: 'COE', name: 'College of Engineering', depts: 5, progs: 12, status: 'Active' },
                { code: 'CN', name: 'College of Nursing', depts: 2, progs: 4, status: 'Active' },
                { code: 'CIT', name: 'College of Information Technology', depts: 3, progs: 6, status: 'Active' },
                { code: 'CBA', name: 'College of Business Administration', depts: 4, progs: 8, status: 'Active' },
                { code: 'CASS', name: 'College of Arts and Social Sciences', depts: 3, progs: 7, status: 'Active' },
                { code: 'CED', name: 'College of Education', depts: 2, progs: 5, status: 'Active' },
                { code: 'CA', name: 'College of Agriculture', depts: 3, progs: 5, status: 'Inactive' },
                { code: 'CL', name: 'College of Law', depts: 1, progs: 2, status: 'Active' },
              ].map((row, i) => (
                <tr key={i} className="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0 group">
                  <td className="px-6 py-4 align-middle">
                    <span className="font-mono text-[13px] font-bold text-[#1a7a41] bg-[#e6f4ec] px-2 py-1 rounded-md">{row.code}</span>
                  </td>
                  <td className="px-6 py-4 align-middle text-[14px] font-bold text-[#0f1f17]">{row.name}</td>
                  <td className="px-6 py-4 align-middle text-[13.5px] font-semibold text-[#4a6356]">{row.depts}</td>
                  <td className="px-6 py-4 align-middle text-[13.5px] font-semibold text-[#4a6356]">{row.progs}</td>
                  <td className="px-6 py-4 align-middle">
                    {row.status === 'Active' ? (
                      <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#dcfce7] text-[#15803d] text-[11.5px] font-bold">
                        <div className="w-1.5 h-1.5 rounded-full bg-[#16a34a]"></div>
                        Active
                      </div>
                    ) : (
                      <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#f3f4f6] text-[#4b5563] text-[11.5px] font-bold">
                        <div className="w-1.5 h-1.5 rounded-full bg-[#6b7280]"></div>
                        Inactive
                      </div>
                    )}
                  </td>
                  <td className="px-6 py-4 align-middle text-right">
                    <div className="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                      <IconButton icon={Eye} title="View Details" />
                      <IconButton icon={Edit} title="Edit Record" />
                      <IconButton icon={Trash2} variant="danger" title="Delete Record" />
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        
        <div className="px-6 py-4 border-t border-[#eaf0ec] flex justify-between items-center bg-[#f8fbf9]">
          <span className="text-[12.5px] font-medium text-[#8aa89a]">Showing 1 to 8 of 8 entries</span>
          <div className="flex items-center gap-1.5">
            <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-[#dde8e1] bg-white text-[#4a6356] hover:text-[#1a7a41] hover:border-[#1a7a41] transition-colors text-[14px] font-medium shadow-sm opacity-50 cursor-not-allowed">‹</button>
            <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-[#1a7a41] bg-[#1a7a41] text-white text-[13px] font-bold shadow-sm">1</button>
            <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-[#dde8e1] bg-white text-[#4a6356] hover:text-[#1a7a41] hover:border-[#1a7a41] transition-colors text-[14px] font-medium shadow-sm opacity-50 cursor-not-allowed">›</button>
          </div>
        </div>
      </div>

      {isCreating && (
        <div className="fixed top-[64px] left-[260px] right-0 bottom-0 z-40 flex items-center justify-center p-4 sm:p-6">
          <div className="absolute inset-0 bg-black/50 backdrop-blur-[2px] transition-opacity" onClick={() => setIsCreating(false)}></div>
          
          <div className="relative bg-white rounded-2xl w-full max-w-lg shadow-2xl z-10 animate-in fade-in zoom-in-95 duration-200">
            <div className="flex items-center justify-between px-6 py-4 border-b border-[#eaf0ec] shrink-0">
              <div>
                <h2 className="text-[18px] font-bold text-[#0f1f17]">Add New College</h2>
                <p className="text-[13px] text-[#4a6356] mt-0.5 font-medium">Create a new college unit in the system.</p>
              </div>
              <button 
                onClick={() => setIsCreating(false)} 
                className="text-[#8aa89a] hover:bg-[#f0f3f1] hover:text-[#0f1f17] p-2 rounded-xl transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
            
            <div className="p-6">
              <div className="mb-5">
                <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">College Code <span className="text-red-500">*</span></label>
                <input 
                  type="text" 
                  placeholder="e.g. COE"
                  className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-[#f8fbf9] text-[14px] font-bold font-mono text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors uppercase"
                />
              </div>
              <div className="mb-5">
                <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">College Name <span className="text-red-500">*</span></label>
                <input 
                  type="text" 
                  placeholder="e.g. College of Engineering"
                  className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors"
                />
              </div>
              <div className="mb-2">
                <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Status</label>
                <select className="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                  <option>Active</option>
                  <option>Inactive</option>
                </select>
              </div>
            </div>

            <div className="px-6 py-4 border-t border-[#eaf0ec] bg-[#f8fbf9] flex justify-end gap-3 shrink-0 rounded-b-2xl">
              <Button variant="outline" onClick={() => setIsCreating(false)}>Cancel</Button>
              <Button variant="primary">Save College</Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

const OrgDashboardPage = () => {
  return (
    <div className="max-w-6xl mx-auto pb-10">
      <PageHeader 
        title="Dashboard Overview" 
        subtitle="Real-time statistics for your organization collections."
        actions={[
          { label: 'Generate Report', icon: Download, variant: 'outline' },
          { label: 'Create Transaction', icon: CreditCard, variant: 'primary' }
        ]}
      />

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        {[
          { label: 'Total Collections', value: '₱45,250.00', icon: CreditCard, color: 'text-[#1a7a41]', bg: 'bg-[#e6f4ec]', border: 'border-[#1a7a41]/20' },
          { label: 'Enrolled Students', value: '1,248', icon: Users, color: 'text-[#d4a42a]', bg: 'bg-[#fdf7e3]', border: 'border-[#d4a42a]/20' },
          { label: 'Pending Voids', value: '3', icon: XCircle, color: 'text-red-600', bg: 'bg-red-50', border: 'border-red-600/20' },
          { label: 'Active Profiles', value: '12', icon: Layers, color: 'text-blue-600', bg: 'bg-blue-50', border: 'border-blue-600/20' },
        ].map((stat, i) => (
          <div key={i} className={`bg-white rounded-2xl border ${stat.border} p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden`}>
            <div className="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-gradient-to-br from-transparent to-black/5 opacity-50 blur-2xl"></div>
            <div className={`float-right w-11 h-11 rounded-xl flex items-center justify-center ${stat.bg} ${stat.color} shadow-sm`}>
              <stat.icon className="w-5.5 h-5.5" />
            </div>
            <div className="text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest mb-2 clear-both">{stat.label}</div>
            <div className="text-[28px] font-extrabold leading-none text-[#0f1f17]">{stat.value}</div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 bg-white rounded-2xl border border-[#dde8e1] p-6 shadow-sm">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-[16px] font-bold text-[#0f1f17]">Collection Trend</h3>
            <select className="border border-[#dde8e1] rounded-lg py-1.5 px-3 text-[12px] font-bold text-[#4a6356] bg-[#f8fbf9] outline-none cursor-pointer">
              <option>Last 7 Days</option>
              <option>This Month</option>
            </select>
          </div>
          <div className="h-[280px] bg-[#f8fbf9] rounded-xl flex flex-col items-center justify-center text-[#8aa89a] text-[13.5px] font-medium border-2 border-dashed border-[#dde8e1]">
             <LayoutDashboard className="w-10 h-10 mb-3 opacity-30 text-[#1a7a41]" />
             Chart Visualization Area
          </div>
        </div>
        <div className="bg-white rounded-2xl border border-[#dde8e1] p-6 shadow-sm flex flex-col">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-[16px] font-bold text-[#0f1f17]">Recent Activity</h3>
            <button className="text-[12px] font-bold text-[#1a7a41] hover:text-[#27a05a]">View All</button>
          </div>
          <div className="flex-1 space-y-4">
             {[
               { name: 'John Smith', type: 'Membership Fee', amount: '+₱150.00', time: 'Today, 10:45 AM', init: 'JS' },
               { name: 'Maria Garcia', type: 'Event Ticket', amount: '+₱300.00', time: 'Today, 09:12 AM', init: 'MG' },
               { name: 'Robert Chen', type: 'Membership Fee', amount: '+₱150.00', time: 'Yesterday', init: 'RC' },
               { name: 'Sarah Wilson', type: 'T-Shirt Fee', amount: '+₱450.00', time: 'Yesterday', init: 'SW' },
               { name: 'David Lee', type: 'Membership Fee', amount: '+₱150.00', time: 'Oct 12', init: 'DL' },
             ].map((item, i) => (
               <div key={i} className="flex items-center justify-between pb-4 border-b border-[#f0f3f1] last:border-0 last:pb-0">
                  <div className="flex items-center gap-3.5">
                     <div className="w-10 h-10 rounded-full bg-[#e6f4ec] text-[#1a7a41] flex items-center justify-center font-bold text-[13px] border border-[#1a7a41]/10">
                        {item.init}
                     </div>
                     <div>
                        <div className="text-[14px] font-bold text-[#0f1f17]">{item.name}</div>
                        <div className="text-[12px] font-medium text-[#8aa89a]">{item.type}</div>
                     </div>
                  </div>
                  <div className="text-right">
                     <div className="text-[14px] font-bold text-[#1a7a41]">{item.amount}</div>
                     <div className="text-[11px] font-medium text-[#8aa89a]">{item.time}</div>
                  </div>
               </div>
             ))}
          </div>
        </div>
      </div>
    </div>
  );
};

// ------------------------------------------------------------------
// REUSABLE GENERIC DATA TABLE PAGE
// ------------------------------------------------------------------
const GenericDataTablePage = ({ pageId }: { pageId: string }) => {
  const [modalMode, setModalMode] = useState<'none' | 'manual' | 'bulk'>('none');
  const [expandedCards, setExpandedCards] = useState<string[]>([]);
  const toggleCard = (id: string) => setExpandedCards(prev => prev.includes(id) ? prev.filter(c => c !== id) : [...prev, id]);

  const mockRemittances = [
    {
      id: 'REM-045', date: 'Oct 15, 2024', cutoff: '5:00 PM', status: 'Pending',
      totalAmount: '₱ 14,500.00',
      breakdown: [
        { collector: 'Juan dela Cruz (Treasurer)', amount: '₱ 8,500.00', transactions: 45 },
        { collector: 'Maria Santos (Collector)', amount: '₱ 6,000.00', transactions: 32 }
      ]
    },
    {
      id: 'REM-044', date: 'Oct 14, 2024', cutoff: '5:00 PM', status: 'Approved',
      totalAmount: '₱ 12,250.00',
      breakdown: [
        { collector: 'Juan dela Cruz (Treasurer)', amount: '₱ 12,250.00', transactions: 70 }
      ]
    }
  ];

  const configs: Record<string, any> = {
    'a-departments': {
      title: 'Manage Departments', subtitle: 'Departments are linked to a parent college (Level 2)',
      actions: [
        { label: 'Import List', icon: Upload, variant: 'outline' },
        { label: 'Add Department', icon: Plus, variant: 'primary' }
      ],
      cols: ['Code', 'Department Name', 'College', 'Programs', 'Status'],
      data: [
        { c1: 'CS', c2: 'Computer Science', c3: 'CIT', c4: '4', c5: 'Active' },
        { c2: 'Information Technology', c1: 'IT', c3: 'CIT', c4: '3', c5: 'Active' },
        { c1: 'CE', c2: 'Civil Engineering', c3: 'COE', c4: '2', c5: 'Active' },
        { c1: 'ME', c2: 'Mechanical Engineering', c3: 'COE', c4: '2', c5: 'Active' },
      ]
    },
    'a-programs': {
      title: 'Manage Programs', subtitle: 'Programs belong to departments (Level 3 — Membership scope)',
      actions: [
        { label: 'Import List', icon: Upload, variant: 'outline' },
        { label: 'Add Program', icon: Plus, variant: 'primary' }
      ],
      cols: ['Code', 'Program Name', 'Department', 'Students', 'Status'],
      data: [
        { c1: 'BSCS', c2: 'BS Computer Science', c3: 'CS', c4: '450', c5: 'Active' },
        { c1: 'BSIT', c2: 'BS Information Technology', c3: 'IT', c4: '620', c5: 'Active' },
        { c1: 'BSCE', c2: 'BS Civil Engineering', c3: 'CE', c4: '380', c5: 'Active' },
      ]
    },
    'a-ay': {
      title: 'Academic Years & Semesters', subtitle: 'Only one semester may be active at a time. All transactions default to the active semester.',
      actions: [
        { label: 'Add Semester', icon: Plus, variant: 'primary' }
      ],
      cols: ['Academic Year', 'Semester', 'Start Date', 'End Date', 'Status'],
      data: [
        { c1: '2024-2025', c2: '2nd Semester', c3: 'Jan 15, 2025', c4: 'May 30, 2025', c5: 'Active' },
        { c1: '2024-2025', c2: '1st Semester', c3: 'Aug 10, 2024', c4: 'Dec 20, 2024', c5: 'Inactive' },
        { c1: '2023-2024', c2: '2nd Semester', c3: 'Jan 10, 2024', c4: 'May 25, 2024', c5: 'Inactive' },
      ]
    },
    'a-organizations': {
      title: 'Manage Organizations', subtitle: 'Each organization is scoped to a hierarchy level (SSC / College / Department)',
      actions: [
        { label: 'Export Data', icon: Download, variant: 'outline' },
        { label: 'Add Organization', icon: Plus, variant: 'primary' }
      ],
      cols: ['Organization Name', 'Level', 'Parent Unit', 'Members', 'Status'],
      data: [
        { c1: 'SSC', c2: 'University', c3: 'CMU', c4: '12,450', c5: 'Active' },
        { c1: 'COE Student Council', c2: 'College', c3: 'COE', c4: '1,248', c5: 'Active' },
        { c1: 'CIT Student Council', c2: 'College', c3: 'CIT', c4: '1,560', c5: 'Active' },
      ]
    },
    'a-students': {
      title: 'Enrolled Students', subtitle: 'SSC-managed master student list · Active Semester: 2024–2025 2nd Sem',
      actions: [
        { label: 'Export Roster', icon: Download, variant: 'outline' },
        { label: 'Bulk Import', icon: Upload, variant: 'primary' },
        { label: 'Add Student', icon: Plus, variant: 'primary' }
      ],
      cols: ['Student ID', 'Name', 'Program', 'College', 'Status'],
      data: [
        { c1: '2024-0001', c2: 'Maria Santos', c3: 'BSCE', c4: 'COE', c5: 'Active' },
        { c1: '2024-0023', c2: 'Juan dela Cruz', c3: 'BSIT', c4: 'CIT', c5: 'Active' },
        { c1: '2023-1452', c2: 'Anna Reyes', c3: 'BSN', c4: 'CN', c5: 'Inactive' },
      ]
    },
    'a-users': {
      title: 'User Management', subtitle: 'All system users across all organizations',
      actions: [
        { label: 'Add User', icon: Plus, variant: 'primary' }
      ],
      cols: ['Name', 'Username', 'Role', 'Organization', 'Status'],
      data: [
        { c1: 'System Admin', c2: 'admin.ssc', c3: 'Super Admin', c4: 'SSC', c5: 'Active' },
        { c1: 'Juan dela Cruz', c2: 'juan.coe', c3: 'Chairperson', c4: 'COE', c5: 'Active' },
      ]
    },
    'a-audit': {
      title: 'Audit Logs', subtitle: 'Immutable system-wide activity trail (FR-0025) · Retained for 5 years',
      actions: [
        { label: 'Export Logs', icon: Download, variant: 'outline' }
      ],
      cols: ['Timestamp', 'User', 'Action', 'Module', 'Details'],
      data: [
        { c1: 'Today 10:45 AM', c2: 'admin.ssc', c3: 'UPDATE', c4: 'Colleges', c5: 'Updated CA status to Inactive' },
        { c1: 'Today 09:12 AM', c2: 'juan.coe', c3: 'CREATE', c4: 'Transaction', c5: 'Created OR COE-00421' },
      ]
    },
    'o-students': {
      title: 'Enrolled Students', subtitle: 'Scoped to COE Student Council · Active Semester',
      actions: [
        { label: 'Export Status', icon: Download, variant: 'outline' },
        { label: 'Bulk Import', icon: Upload, variant: 'primary' },
        { label: 'Add Student', icon: Plus, variant: 'primary' }
      ],
      cols: ['Student ID', 'Name', 'Program', 'Membership', 'Payment Status'],
      data: [
        { c1: '2024-0001', c2: 'Maria Santos', c3: 'BSCE', c4: 'Regular', c5: 'Paid' },
        { c1: '2024-0142', c2: 'Pedro Penduko', c3: 'BSME', c4: 'Regular', c5: 'Pending' },
      ]
    },
    'o-remittance': {
      title: 'Remittance', subtitle: 'Generate and track remittance reports securely',
      actions: [
        { label: 'Export History', icon: Download, variant: 'outline' },
        { label: 'Create Remittance', icon: Plus, variant: 'primary' }
      ],
      cols: ['Batch ID', 'Date Created', 'Amount', 'Remitted By', 'Status'],
      data: [
        { c1: 'REM-045', c2: 'Oct 15, 2024', c3: '₱ 14,500.00', c4: 'Juan dela Cruz', c5: 'Verified' },
        { c1: 'REM-044', c2: 'Oct 01, 2024', c3: '₱ 12,250.00', c4: 'Juan dela Cruz', c5: 'Accepted' },
      ]
    },
    'o-feeprofiles': {
      title: 'Fee Profiles', subtitle: 'Standardized collection rules for automated POS entry',
      actions: [
        { label: 'Create Profile', icon: Plus, variant: 'primary' }
      ],
      cols: ['Profile Name', 'Target Category', 'Amount', 'Type', 'Status'],
      data: [
        { c1: 'Membership 2024', c2: 'Regular', c3: '₱ 150.00', c4: 'Mandatory', c5: 'Active' },
        { c1: 'Event Ticket (Opt)', c2: 'All', c3: '₱ 300.00', c4: 'Optional', c5: 'Active' },
      ]
    },
    'o-users': {
      title: 'User Management', subtitle: 'Manage organization officers and their access levels',
      actions: [
        { label: 'Invite User', icon: Plus, variant: 'primary' }
      ],
      cols: ['Name', 'Role', 'Email', 'Last Login', 'Status'],
      data: [
        { c1: 'Juan dela Cruz', c2: 'Chairperson', c3: 'juan@cmu.edu.ph', c4: 'Today', c5: 'Active' },
        { c1: 'Jane Doe', c2: 'Treasurer', c3: 'jane@cmu.edu.ph', c4: 'Yesterday', c5: 'Active' },
      ]
    },
    'o-audit': {
      title: 'Audit Logs', subtitle: 'Activity history for your organization',
      actions: [
        { label: 'Export Logs', icon: Download, variant: 'outline' }
      ],
      cols: ['Timestamp', 'User', 'Action', 'Module', 'Details'],
      data: [
        { c1: 'Today 10:45 AM', c2: 'Jane Doe', c3: 'CREATE', c4: 'Transaction', c5: 'Created OR COE-00421' },
        { c1: 'Yesterday 04:30 PM', c2: 'Juan dela Cruz', c3: 'APPROVE', c4: 'Void Request', c5: 'Approved VR-2024-011' },
      ]
    }
  };

  const config = configs[pageId] || {
    title: 'Page Not Configured', subtitle: 'This view is dynamically rendered as a fallback.', 
    actions: [{ label: 'Action', icon: Search, variant: 'primary' }],
    cols: ['Column 1', 'Column 2', 'Column 3'], data: [{ c1: 'Data', c2: 'Data', c3: 'Data' }]
  };

  const mappedActions = config.actions?.map((action: any) => {
    if (action.label.startsWith('Add ') || action.label === 'Create Profile' || action.label === 'Create Remittance' || action.label === 'Invite User') {
      return { ...action, onClick: () => setModalMode('manual') };
    }
    if (action.label === 'Bulk Import' || action.label.startsWith('Import ')) {
      return { ...action, onClick: () => setModalMode('bulk') };
    }
    return action;
  });

  return (
    <div className="max-w-6xl mx-auto pb-10 relative">
      <PageHeader title={config.title} subtitle={config.subtitle} actions={mappedActions || config.actions} />

      {pageId === 'o-remittance' ? (
        <div className="space-y-5">
          {mockRemittances.map(rem => {
             const isExpanded = expandedCards.includes(rem.id);
             return (
               <div key={rem.id} className="bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden">
                  <div className="p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                     <div className="flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-[#f8fbf9] border border-[#dde8e1] flex flex-col items-center justify-center text-[#1a7a41]">
                           <span className="text-[10px] font-bold uppercase tracking-wider text-[#8aa89a] leading-none mb-0.5">OCT</span>
                           <span className="text-[16px] font-extrabold leading-none">{rem.date.split(' ')[1].replace(',', '')}</span>
                        </div>
                        <div>
                           <div className="flex items-center gap-2 mb-1">
                             <h3 className="text-[16px] font-bold text-[#0f1f17]">{rem.date}</h3>
                             {rem.status === 'Pending' ? (
                               <span className="px-2 py-0.5 bg-[#fef9c3] text-[#ca8a04] text-[11px] font-bold rounded-md flex items-center gap-1"><div className="w-1.5 h-1.5 rounded-full bg-[#eab308]"></div>Pending</span>
                             ) : (
                               <span className="px-2 py-0.5 bg-[#dcfce7] text-[#15803d] text-[11px] font-bold rounded-md flex items-center gap-1"><div className="w-1.5 h-1.5 rounded-full bg-[#16a34a]"></div>Approved</span>
                             )}
                           </div>
                           <p className="text-[13px] text-[#4a6356] font-medium flex items-center gap-1.5">
                             <FileClock className="w-3.5 h-3.5" /> Cutoff: {rem.cutoff} · Ref: {rem.id}
                           </p>
                        </div>
                     </div>
                     <div className="flex flex-col md:items-end gap-2">
                        <div className="text-[20px] font-extrabold text-[#0f1f17]">{rem.totalAmount}</div>
                        {rem.status === 'Pending' && (
                           <div className="flex items-center gap-2">
                              <button className="px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-[12px] font-bold transition-colors">Reject</button>
                              <button className="px-3 py-1.5 rounded-lg bg-[#1a7a41] text-white hover:bg-[#27a05a] text-[12px] font-bold shadow-sm transition-colors">Approve</button>
                           </div>
                        )}
                     </div>
                  </div>
                  <div className="border-t border-[#eaf0ec] bg-[#f8fbf9]">
                     <button 
                       onClick={() => toggleCard(rem.id)}
                       className="w-full px-6 py-3 flex items-center justify-between text-[13px] font-bold text-[#4a6356] hover:bg-[#f0f3f1] transition-colors"
                     >
                       <span className="flex items-center gap-2"><Users className="w-4 h-4" /> View Collector Breakdown</span>
                       {isExpanded ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
                     </button>
                     {isExpanded && (
                       <div className="px-6 pb-6 pt-2">
                         <div className="bg-white border border-[#dde8e1] rounded-xl overflow-hidden">
                           <table className="w-full text-left border-collapse">
                             <thead>
                               <tr className="bg-[#f8fbf9] border-b border-[#dde8e1]">
                                 <th className="px-4 py-2.5 text-[11.5px] font-bold text-[#8aa89a] uppercase">Collector</th>
                                 <th className="px-4 py-2.5 text-[11.5px] font-bold text-[#8aa89a] uppercase text-right">Transactions</th>
                                 <th className="px-4 py-2.5 text-[11.5px] font-bold text-[#8aa89a] uppercase text-right">Amount</th>
                               </tr>
                             </thead>
                             <tbody>
                               {rem.breakdown.map((bd, i) => (
                                 <tr key={i} className="border-b border-[#eaf0ec] last:border-0 hover:bg-[#f8fbf9]">
                                   <td className="px-4 py-3 text-[13px] font-bold text-[#0f1f17]">{bd.collector}</td>
                                   <td className="px-4 py-3 text-[13px] font-medium text-[#4a6356] text-right">{bd.transactions}</td>
                                   <td className="px-4 py-3 text-[13px] font-bold text-[#1a7a41] text-right">{bd.amount}</td>
                                 </tr>
                               ))}
                             </tbody>
                           </table>
                         </div>
                       </div>
                     )}
                  </div>
               </div>
             )
          })}
        </div>
      ) : (
      <div className="bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-5 border-b border-[#eaf0ec]">
          <div>
            <h3 className="text-[15px] font-bold text-[#0f1f17]">Data Records</h3>
            <p className="text-[12.5px] text-[#8aa89a] font-medium mt-0.5">{config.data.length} total records</p>
          </div>
          <div className="flex flex-wrap items-center gap-2.5">
            <div className="relative w-full md:w-[240px]">
              <Search className="w-4 h-4 text-[#8aa89a] absolute left-3 top-1/2 -translate-y-1/2" />
              <input 
                type="text" 
                placeholder="Search records..." 
                className="w-full pl-9 pr-3 py-2 border-2 border-[#dde8e1] rounded-xl text-[13px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] hover:border-[#b7dfc7] transition-colors"
              />
            </div>
            <button className="px-3 py-2 border-2 border-[#dde8e1] rounded-xl text-[#4a6356] hover:border-[#1a7a41] hover:text-[#1a7a41] transition-colors flex items-center gap-2">
              <Filter className="w-4 h-4" /> <span className="text-[13px] font-bold">Filter</span>
            </button>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse min-w-[700px]">
            <thead>
              <tr className="bg-[#f8fbf9] border-b border-[#dde8e1]">
                {config.cols.map((col: string, i: number) => (
                  <th key={i} className="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest whitespace-nowrap">{col}</th>
                ))}
                <th className="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest whitespace-nowrap text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {config.data.map((row: any, i: number) => (
                <tr key={i} className="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0 group">
                  <td className="px-6 py-4 align-middle">
                    <span className="font-mono text-[13px] font-bold text-[#1a7a41] bg-[#e6f4ec] px-2 py-1 rounded-md">{row.c1}</span>
                  </td>
                  <td className="px-6 py-4 align-middle text-[14px] font-bold text-[#0f1f17]">{row.c2}</td>
                  <td className="px-6 py-4 align-middle text-[13.5px] font-semibold text-[#4a6356]">{row.c3}</td>
                  <td className="px-6 py-4 align-middle text-[13.5px] font-semibold text-[#4a6356]">{row.c4}</td>
                  <td className="px-6 py-4 align-middle">
                    {row.c5 === 'Active' || row.c5 === 'Approved' || row.c5 === 'Verified' || row.c5 === 'Paid' || row.c5 === 'Accepted' ? (
                      <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#dcfce7] text-[#15803d] text-[11.5px] font-bold">
                        <div className="w-1.5 h-1.5 rounded-full bg-[#16a34a]"></div>
                        {row.c5}
                      </div>
                    ) : row.c5 === 'Pending' ? (
                      <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#fef9c3] text-[#ca8a04] text-[11.5px] font-bold">
                        <div className="w-1.5 h-1.5 rounded-full bg-[#eab308]"></div>
                        {row.c5}
                      </div>
                    ) : (
                      <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#f3f4f6] text-[#4b5563] text-[11.5px] font-bold">
                        <div className="w-1.5 h-1.5 rounded-full bg-[#6b7280]"></div>
                        {row.c5}
                      </div>
                    )}
                  </td>
                  <td className="px-6 py-4 align-middle text-right">
                    <div className="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                      <IconButton icon={Eye} title="View Details" />
                      <IconButton icon={Edit} title="Edit Record" />
                      {pageId !== 'a-audit' && pageId !== 'o-audit' && (
                        <IconButton icon={Trash2} variant="danger" title="Delete Record" />
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        
        <div className="px-6 py-4 border-t border-[#eaf0ec] flex justify-between items-center bg-[#f8fbf9]">
          <span className="text-[12.5px] font-medium text-[#8aa89a]">Showing {config.data.length} entries</span>
          <div className="flex items-center gap-1.5">
            <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-[#dde8e1] bg-white text-[#4a6356] shadow-sm opacity-50 cursor-not-allowed">‹</button>
            <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-[#1a7a41] bg-[#1a7a41] text-white text-[13px] font-bold shadow-sm">1</button>
            <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-[#dde8e1] bg-white text-[#4a6356] shadow-sm opacity-50 cursor-not-allowed">›</button>
          </div>
        </div>
      </div>
      )}

      {modalMode !== 'none' && (
        <div className="fixed top-[64px] left-[260px] right-0 bottom-0 z-40 flex items-center justify-center p-4 sm:p-6">
          <div className="absolute inset-0 bg-black/50 backdrop-blur-[2px] transition-opacity" onClick={() => setModalMode('none')}></div>
          
          <div className="relative bg-white rounded-2xl w-full max-w-2xl shadow-2xl z-10 animate-in fade-in zoom-in-95 duration-200 flex flex-col max-h-[90vh]">
            <div className="flex items-center justify-between px-6 py-4 border-b border-[#eaf0ec] shrink-0">
              <div>
                <h2 className="text-[18px] font-bold text-[#0f1f17]">
                  {modalMode === 'bulk' ? 'Bulk Import Records' :
                   pageId === 'a-departments' ? 'Add New Department' : 
                   pageId === 'a-programs' ? 'Add New Program' : 
                   pageId === 'a-ay' ? 'Add New Semester' : 
                   pageId === 'a-organizations' ? 'Add New Organization' : 
                   (pageId === 'a-students' || pageId === 'o-students') ? 'Enroll New Student' :
                   pageId === 'a-users' ? 'Add New User' :
                   pageId === 'o-users' ? 'Invite New User' :
                   pageId === 'o-feeprofiles' ? 'Create Fee Profile' :
                   pageId === 'o-remittance' ? 'Create Remittance' :
                   'Add New Record'}
                </h2>
                <p className="text-[13px] text-[#4a6356] mt-0.5 font-medium">
                  {modalMode === 'bulk' ? 'Upload a CSV or Excel file to import multiple records at once.' : 'Create a new entry in the system.'}
                </p>
              </div>
              <button 
                onClick={() => setModalMode('none')} 
                className="text-[#8aa89a] hover:bg-[#f0f3f1] hover:text-[#0f1f17] p-2 rounded-xl transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
            
            <div className="p-6 overflow-y-auto scrollbar-thin">
              {modalMode === 'bulk' ? (
                <div className="flex flex-col items-center justify-center border-2 border-dashed border-[#dde8e1] rounded-xl bg-[#f8fbf9] p-8 text-center">
                  <Upload className="w-12 h-12 text-[#1a7a41] mb-4 opacity-50" />
                  <h3 className="text-[15px] font-bold text-[#0f1f17] mb-2">Drag and drop your file here</h3>
                  <p className="text-[13px] text-[#4a6356] mb-6 max-w-sm">
                    Supported file formats: .csv, .xlsx, .xls. Make sure your file follows the standard template format.
                  </p>
                  <Button variant="outline">Browse Files</Button>
                  <div className="mt-6 pt-6 border-t border-[#dde8e1] w-full text-left">
                    <h4 className="text-[13px] font-bold text-[#0f1f17] mb-2">Need the template?</h4>
                    <a href="#" className="text-[13px] font-semibold text-[#1a7a41] hover:underline flex items-center gap-1">
                      <Download className="w-4 h-4" /> Download Standard Template
                    </a>
                  </div>
                </div>
              ) : (
                <>
                  {pageId === 'a-departments' && (
                     <>
                       <div className="mb-5">
                         <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Department Code <span className="text-red-500">*</span></label>
                         <input type="text" placeholder="e.g. CS" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-[#f8fbf9] text-[14px] font-bold font-mono text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors uppercase" />
                       </div>
                       <div className="mb-5">
                         <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Department Name <span className="text-red-500">*</span></label>
                         <input type="text" placeholder="e.g. Computer Science" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                       </div>
                       <div className="mb-2">
                         <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Parent College <span className="text-red-500">*</span></label>
                         <select className="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                           <option>College of Engineering (COE)</option>
                           <option>College of Information Technology (CIT)</option>
                         </select>
                       </div>
                     </>
                  )}

                  {pageId === 'a-programs' && (
                     <>
                       <div className="mb-5">
                         <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Program Code <span className="text-red-500">*</span></label>
                         <input type="text" placeholder="e.g. BSCS" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-[#f8fbf9] text-[14px] font-bold font-mono text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors uppercase" />
                       </div>
                       <div className="mb-5">
                         <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Program Name <span className="text-red-500">*</span></label>
                         <input type="text" placeholder="e.g. BS Computer Science" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                       </div>
                       <div className="mb-2">
                         <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Parent Department <span className="text-red-500">*</span></label>
                         <select className="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                           <option>Computer Science (CS)</option>
                           <option>Information Technology (IT)</option>
                         </select>
                       </div>
                     </>
                  )}

                  {pageId === 'a-ay' && (
                     <>
                       <div className="mb-5">
                         <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Academic Year <span className="text-red-500">*</span></label>
                         <input type="text" placeholder="e.g. 2024-2025" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                       </div>
                       <div className="mb-5">
                         <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Semester <span className="text-red-500">*</span></label>
                         <select className="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                           <option>1st Semester</option>
                           <option>2nd Semester</option>
                           <option>Midyear</option>
                         </select>
                       </div>
                       <div className="grid grid-cols-2 gap-4 mb-2">
                         <div>
                           <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Start Date <span className="text-red-500">*</span></label>
                           <input type="date" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#4a6356] outline-none focus:border-[#1a7a41] transition-colors" />
                         </div>
                         <div>
                           <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">End Date <span className="text-red-500">*</span></label>
                           <input type="date" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#4a6356] outline-none focus:border-[#1a7a41] transition-colors" />
                         </div>
                       </div>
                     </>
                  )}

                  {pageId === 'a-organizations' && (
                     <>
                       <div className="mb-5">
                         <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Organization Name <span className="text-red-500">*</span></label>
                         <input type="text" placeholder="e.g. COE Student Council" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                       </div>
                       <div className="mb-5">
                         <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Hierarchy Level <span className="text-red-500">*</span></label>
                         <select className="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                           <option>University (SSC)</option>
                           <option>College Level</option>
                           <option>Department Level</option>
                           <option>Program Level</option>
                         </select>
                       </div>
                       <div className="mb-2">
                         <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Parent Unit (Scope) <span className="text-red-500">*</span></label>
                         <select className="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                           <option>College of Engineering (COE)</option>
                           <option>College of Information Technology (CIT)</option>
                         </select>
                       </div>
                     </>
                  )}

                  {(pageId === 'a-students' || pageId === 'o-students') && (
                     <div className="space-y-6">
                       <div>
                         <h3 className="text-[14px] font-bold text-[#1a7a41] mb-4 pb-2 border-b border-[#dde8e1]">Student Identity</h3>
                         <div className="mb-4">
                           <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Student Number <span className="text-red-500">*</span></label>
                           <input type="text" placeholder="e.g. 2023-001" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-[#f8fbf9] text-[14px] font-bold font-mono text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                         </div>
                         <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">First Name <span className="text-red-500">*</span></label>
                             <input type="text" placeholder="e.g. Juan" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                           </div>
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Last Name <span className="text-red-500">*</span></label>
                             <input type="text" placeholder="e.g. Dela Cruz" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                           </div>
                         </div>
                         <div className="mb-2">
                           <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Middle Name <span className="text-[11px] font-normal text-[#8aa89a] ml-1">(Optional)</span></label>
                           <input type="text" placeholder="e.g. Santos" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                         </div>
                       </div>

                       <div>
                         <h3 className="text-[14px] font-bold text-[#1a7a41] mb-4 pb-2 border-b border-[#dde8e1]">Enrollment Details</h3>
                         <div className="mb-4">
                           <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Academic Year / Semester <span className="text-red-500">*</span></label>
                           <input type="text" value="2024-2025 (2nd Semester)" readOnly className="w-full px-4 py-2.5 border-2 border-[#eaf0ec] rounded-xl bg-[#f0f3f1] text-[14px] font-semibold text-[#4a6356] outline-none cursor-not-allowed" />
                         </div>
                         <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">College <span className="text-red-500">*</span></label>
                             <select className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                               <option>College of Engineering</option>
                               <option>College of Nursing</option>
                             </select>
                           </div>
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Department <span className="text-red-500">*</span></label>
                             <select className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                               <option>Civil Engineering</option>
                               <option>Mechanical Engineering</option>
                             </select>
                           </div>
                         </div>
                         <div className="mb-4">
                           <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Program / Course <span className="text-red-500">*</span></label>
                           <select className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                             <option>BS Civil Engineering</option>
                             <option>BS Mechanical Engineering</option>
                           </select>
                         </div>
                         <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-2">
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Year Level <span className="text-red-500">*</span></label>
                             <select className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                               <option>1st Year</option>
                               <option>2nd Year</option>
                               <option>3rd Year</option>
                               <option>4th Year</option>
                               <option>5th Year</option>
                             </select>
                           </div>
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Student Type <span className="text-red-500">*</span></label>
                             <select className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                               <option>Regular</option>
                               <option>Irregular</option>
                             </select>
                           </div>
                         </div>
                       </div>
                     </div>
                  )}

                  {pageId === 'a-users' && (
                     <div className="space-y-6">
                       <div>
                         <h3 className="text-[14px] font-bold text-[#1a7a41] mb-4 pb-2 border-b border-[#dde8e1]">Basic Information</h3>
                         <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">First Name <span className="text-red-500">*</span></label>
                             <input type="text" placeholder="e.g. Juan" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                           </div>
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Last Name <span className="text-red-500">*</span></label>
                             <input type="text" placeholder="e.g. Dela Cruz" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                           </div>
                         </div>
                         <div className="mb-2">
                           <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Email Address <span className="text-red-500">*</span></label>
                           <input type="email" placeholder="e.g. juan@cmu.edu.ph" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                         </div>
                       </div>
                       
                       <div>
                         <h3 className="text-[14px] font-bold text-[#1a7a41] mb-4 pb-2 border-b border-[#dde8e1]">Account & Access</h3>
                         <div className="mb-4">
                           <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Username <span className="text-red-500">*</span></label>
                           <input type="text" placeholder="e.g. juan.delacruz" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-[#f8fbf9] text-[14px] font-bold font-mono text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                         </div>
                         <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-2">
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">System Role <span className="text-red-500">*</span></label>
                             <select className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                               <option>Super Admin</option>
                               <option>Organization Admin</option>
                               <option>Auditor</option>
                             </select>
                           </div>
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Organization Scope <span className="text-red-500">*</span></label>
                             <select className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                               <option>Global (System Wide)</option>
                               <option>Supreme Student Council (SSC)</option>
                               <option>COE Student Council</option>
                               <option>CIT Student Council</option>
                             </select>
                           </div>
                         </div>
                       </div>
                     </div>
                  )}

                  {pageId === 'o-users' && (
                     <div className="space-y-6">
                       <div>
                         <h3 className="text-[14px] font-bold text-[#1a7a41] mb-4 pb-2 border-b border-[#dde8e1]">Basic Information</h3>
                         <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">First Name <span className="text-red-500">*</span></label>
                             <input type="text" placeholder="e.g. Juan" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                           </div>
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Last Name <span className="text-red-500">*</span></label>
                             <input type="text" placeholder="e.g. Dela Cruz" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                           </div>
                         </div>
                         <div className="mb-2">
                           <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Email Address <span className="text-red-500">*</span></label>
                           <input type="email" placeholder="e.g. juan@cmu.edu.ph" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                           <p className="text-[12px] text-[#8aa89a] mt-1.5">An invitation link will be sent to this email address.</p>
                         </div>
                       </div>
                       
                       <div>
                         <h3 className="text-[14px] font-bold text-[#1a7a41] mb-4 pb-2 border-b border-[#dde8e1]">Access Level</h3>
                         <div className="mb-2">
                           <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Organization Role <span className="text-red-500">*</span></label>
                           <select className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                             <option>Chairperson</option>
                             <option>Vice Chairperson</option>
                             <option>Secretary</option>
                             <option>Treasurer</option>
                             <option>Auditor</option>
                             <option>Committee Head</option>
                           </select>
                         </div>
                       </div>
                     </div>
                  )}

                  {pageId === 'o-feeprofiles' && (
                     <div className="space-y-6">
                       <div>
                         <h3 className="text-[14px] font-bold text-[#1a7a41] mb-4 pb-2 border-b border-[#dde8e1]">Profile Details</h3>
                         <div className="mb-4">
                           <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Profile Name <span className="text-red-500">*</span></label>
                           <input type="text" placeholder="e.g. SSC Membership Fee 2024" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                         </div>
                         <div className="mb-2">
                           <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Amount <span className="text-red-500">*</span></label>
                           <div className="relative">
                             <span className="absolute left-4 top-1/2 -translate-y-1/2 text-[#4a6356] font-bold">₱</span>
                             <input type="number" placeholder="0.00" className="w-full pl-8 pr-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-bold font-mono text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                           </div>
                         </div>
                       </div>
                       
                       <div>
                         <h3 className="text-[14px] font-bold text-[#1a7a41] mb-4 pb-2 border-b border-[#dde8e1]">Collection Rules</h3>
                         <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-2">
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Collection Type <span className="text-red-500">*</span></label>
                             <select className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                               <option>Mandatory</option>
                               <option>Optional</option>
                             </select>
                           </div>
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Target Category <span className="text-red-500">*</span></label>
                             <select className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                               <option>All Students</option>
                               <option>Regular Students Only</option>
                               <option>Irregular Students Only</option>
                             </select>
                           </div>
                         </div>
                       </div>
                     </div>
                  )}

                  {pageId === 'o-remittance' && (
                     <div className="space-y-6">
                       <div>
                         <h3 className="text-[14px] font-bold text-[#1a7a41] mb-4 pb-2 border-b border-[#dde8e1]">Remittance Details</h3>
                         <div className="mb-4">
                           <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Description / Title <span className="text-red-500">*</span></label>
                           <input type="text" placeholder="e.g. September 2024 Collections Batch 1" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                         </div>
                         <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-2">
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Amount to Remit <span className="text-red-500">*</span></label>
                             <div className="relative">
                               <span className="absolute left-4 top-1/2 -translate-y-1/2 text-[#4a6356] font-bold">₱</span>
                               <input type="number" placeholder="0.00" className="w-full pl-8 pr-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-bold font-mono text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                             </div>
                           </div>
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Deposit Date <span className="text-red-500">*</span></label>
                             <input type="date" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors" />
                           </div>
                         </div>
                       </div>
                       
                       <div>
                         <h3 className="text-[14px] font-bold text-[#1a7a41] mb-4 pb-2 border-b border-[#dde8e1]">Deposit Information</h3>
                         <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Deposit Channel <span className="text-red-500">*</span></label>
                             <select className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                               <option>University Cashier</option>
                               <option>Bank Deposit (LBP)</option>
                               <option>GCash / E-Wallet</option>
                             </select>
                           </div>
                           <div>
                             <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Reference / OR Number <span className="text-red-500">*</span></label>
                             <input type="text" placeholder="e.g. OR-2024-9102" className="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-[#f8fbf9] text-[14px] font-bold font-mono text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors uppercase" />
                           </div>
                         </div>
                         <div className="mb-2">
                           <label className="block text-[13px] font-semibold text-[#4a6356] mb-2">Proof of Deposit <span className="text-red-500">*</span></label>
                           <div className="flex items-center gap-3 w-full px-4 py-3 border-2 border-dashed border-[#dde8e1] rounded-xl bg-[#f8fbf9] text-[#4a6356] cursor-pointer hover:border-[#1a7a41] hover:text-[#1a7a41] transition-colors">
                             <Upload className="w-5 h-5" />
                             <span className="text-[13px] font-medium">Click to upload deposit slip or receipt (JPG, PNG, PDF)</span>
                           </div>
                         </div>
                       </div>
                     </div>
                  )}

                  {/* Fallback for other pages if Add is clicked */}
                  {pageId !== 'a-departments' && pageId !== 'a-programs' && pageId !== 'a-ay' && pageId !== 'a-organizations' && pageId !== 'a-students' && pageId !== 'o-students' && pageId !== 'a-users' && pageId !== 'o-users' && pageId !== 'o-feeprofiles' && pageId !== 'o-remittance' && (
                    <div className="text-[13px] text-[#4a6356] font-medium p-4 bg-[#f8fbf9] rounded-xl border border-[#dde8e1] text-center">
                      Form configuration for this module is not yet implemented.
                    </div>
                  )}
                </>
              )}
            </div>

            <div className="px-6 py-4 border-t border-[#eaf0ec] bg-[#f8fbf9] flex justify-end gap-3 shrink-0 rounded-b-2xl">
              <Button variant="outline" onClick={() => setModalMode('none')}>Cancel</Button>
              <Button variant="primary">
                {modalMode === 'bulk' ? 'Process Upload' : 'Save Record'}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
