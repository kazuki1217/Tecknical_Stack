import { ReactNode } from "react";
import { useNavigate } from "react-router-dom";

interface LayoutProps {
  children: ReactNode;
  onLogout: () => void;
}

function Layout({ children, onLogout }: LayoutProps) {
  const navigate = useNavigate();

  return (
    <div style={{ display: "flex", height: "100vh" }}>
      {/* サイドバー */}
      <div style={{ width: "200px", background: "#f0f0f0", padding: "1rem", boxSizing: "border-box" }}>
        <h3>メニュー</h3>
        <ul style={{ listStyle: "none", padding: 0 }}>
          <li>
            <button onClick={() => navigate("/posts")}>PostList</button>
          </li>
          <li>
            <button onClick={() => navigate("/search")}>検索</button>
          </li>
          <li>
            <button onClick={onLogout}>ログアウト</button>
          </li>
        </ul>
      </div>

      {/* メインコンテンツ */}
      <div style={{ flex: 1, padding: "2rem", overflowY: "auto" }}>{children}</div>
    </div>
  );
}

export default Layout;
