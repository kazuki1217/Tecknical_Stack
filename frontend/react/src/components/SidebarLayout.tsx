import { ReactNode } from "react";
import { useNavigate } from "react-router-dom";
import { FaList, FaSearch, FaSignOutAlt } from "react-icons/fa";

import "../styles/SidebarLayout.css";

interface SidebarLayoutProps {
  loggedInUserName: string | null;
  children: ReactNode;
}

/**
 * サイドバーコンポーネント
 *
 * @param loggedInUserName - ログイン中のユーザ名
 * @param children - メインコンテンツとして描画するコンポーネント
 * @param onLogout - ログアウト時に呼ばれる関数
 * @returns JSX.Element
 */
function SidebarLayout({ loggedInUserName, children }: SidebarLayoutProps) {
  const navigate = useNavigate();

  /** ログアウト処理 */
  const handleLogout = () => {
    localStorage.removeItem("token");
    navigate("/");
    window.location.reload(); // App.tsx を再評価させて状態をリセット
  };

  return (
    <div>
      {/* サイドバー */}
      <div className="sidebar">
        <div className="sidebar-username">{loggedInUserName} さん</div>
        <SidebarItem icon={<FaList />} label="投稿一覧" onClick={() => navigate("/posts")} />
        <SidebarItem icon={<FaSearch />} label="検索" onClick={() => navigate("/search")} />
        <SidebarItem icon={<FaSignOutAlt />} label="ログアウト" onClick={() => handleLogout()} />
      </div>

      {/* メインコンテンツ */}
      <div className="main-content">{children}</div>
    </div>
  );
}

export default SidebarLayout;

interface SidebarItemProps {
  icon: ReactNode;
  label: string;
  onClick: () => void;
}

/**
 * サイドバーアイテムコンポーネント
 *
 * @param icon - 表示するアイコン（ReactNode）
 * @param label - 項目のラベル文字列
 * @param onClick - クリック時に呼び出す処理
 * @returns JSX.Element

 */
function SidebarItem({ icon, label, onClick }: SidebarItemProps) {
  return (
    <div onClick={onClick} className="sidebar-item">
      <div className="sidebar-item__icon">{icon}</div>
      <span className="sidebar-item__label">{label}</span>
    </div>
  );
}
