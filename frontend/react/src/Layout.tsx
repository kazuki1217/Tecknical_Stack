import { ReactNode } from "react";
import { useNavigate } from "react-router-dom";
import { FaList, FaSearch, FaSignOutAlt } from "react-icons/fa";
import "./index.css";

interface LayoutProps {
  children: ReactNode;
  onLogout: () => void;
}

/** サイドバー全体を構成 */
function Layout({ children, onLogout }: LayoutProps) {
  const navigate = useNavigate();

  return (
    <div>
      {/* サイドバー */}
      <div className="sidebar">
        {/* 投稿一覧 */}
        <SidebarItem icon={<FaList />} label="投稿一覧" onClick={() => navigate("/posts")} />

        {/* 検索 */}
        <SidebarItem icon={<FaSearch />} label="検索" onClick={() => navigate("/search")} />

        {/* ログアウト */}
        <SidebarItem icon={<FaSignOutAlt />} label="ログアウト" onClick={onLogout} />
      </div>

      {/* メインコンテンツ */}
      <div className="main-content">{children}</div>
    </div>
  );
}

export default Layout;

interface SidebarItemProps {
  icon: ReactNode;
  label: string;
  onClick: () => void;
}

/** サイドバー内の各項目（アイコンとラベル）を構成 */
function SidebarItem({ icon, label, onClick }: SidebarItemProps) {
  return (
    <div onClick={onClick} className="sidebar-item">
      <div className="sidebar-item__icon">{icon}</div>
      <span className="sidebar-item__label">{label}</span>
    </div>
  );
}
