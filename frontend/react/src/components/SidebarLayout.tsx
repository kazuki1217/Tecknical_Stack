import { ReactNode, useState } from "react";
import { useNavigate } from "react-router-dom";
import { FaList, FaSearch, FaSignOutAlt, FaBars, FaTimes } from "react-icons/fa";

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
  const [isSidebarOpen, setIsSidebarOpen] = useState(false); // スマホ・タブレット幅でのサイドバー開閉状態を管理

  /** ログアウト処理 */
  const handleLogout = () => {
    localStorage.removeItem("token");
    navigate("/");
    window.location.reload(); // App.tsx を再評価させて状態をリセット
  };

  /** サイドバーの項目クリック時の共通処理（画面遷移後、狭い画面で開いていたサイドバーを閉じる） */
  const handleNavigate = (path: string) => {
    navigate(path);
    setIsSidebarOpen(false);
  };

  return (
    <div>
      {/* サイドバー開閉ボタン（PC幅では非表示、スマホ・タブレット幅でのみ表示） */}
      <button className="sidebar-toggle-button" onClick={() => setIsSidebarOpen((prev) => !prev)} aria-label="メニューを開閉">
        {isSidebarOpen ? <FaTimes /> : <FaBars />}
      </button>

      {/* サイドバーを開いたときの背景オーバーレイ（クリックで閉じる） */}
      {isSidebarOpen && <div className="sidebar-overlay" onClick={() => setIsSidebarOpen(false)} />}

      {/* サイドバー */}
      <div className={`sidebar${isSidebarOpen ? " sidebar--open" : ""}`}>
        <div className="sidebar-username">{loggedInUserName} さん</div>
        <SidebarItem icon={<FaList />} label="投稿一覧" onClick={() => handleNavigate("/posts")} />
        <SidebarItem icon={<FaSearch />} label="検索" onClick={() => handleNavigate("/search")} />
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
