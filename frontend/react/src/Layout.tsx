import { ReactNode } from "react";
import { useNavigate } from "react-router-dom";
import { FaList, FaSearch, FaSignOutAlt } from "react-icons/fa";
import "./index.css";

interface LayoutProps {
  children: ReactNode;
  onLogout: () => void;
}

function Layout({ children, onLogout }: LayoutProps) {
  const navigate = useNavigate();

  return (
    <div style={{ display: "flex", height: "100vh" }}>
      {/* サイドバー */}
      <div className="sidebar">
        {/* 投稿一覧 */}
        <SidebarIcon icon={<FaList size="24" color="#fff" />} label="投稿一覧" onClick={() => navigate("/posts")} />

        {/* 検索 */}
        <SidebarIcon icon={<FaSearch size="24" color="#fff" />} label="検索" onClick={() => navigate("/search")} />

        {/* ログアウト */}
        <SidebarIcon icon={<FaSignOutAlt size="24" color="#fff" />} label="ログアウト" onClick={onLogout} />
      </div>

      {/* メインコンテンツ */}
      <div className="main-content">{children}</div>
    </div>
  );
}

export default Layout;

/**
 * サイドバーアイコンコンポーネント
 */
interface SidebarIconProps {
  icon: ReactNode;
  label: string;
  onClick: () => void;
}

function SidebarIcon({ icon, label, onClick }: SidebarIconProps) {
  return (
    <div onClick={onClick} className="sidebar-icon">
      {icon}
      <span className="sidebar-label">{label}</span>
    </div>
  );
}
