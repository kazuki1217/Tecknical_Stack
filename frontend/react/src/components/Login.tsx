import { useState } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";

import "../styles/Login.css";

interface LoginProps {
  setIsLoggedIn: React.Dispatch<React.SetStateAction<boolean | null>>;
  setUser: React.Dispatch<React.SetStateAction<string | null>>;
}

/**
 * ログイン画面コンポーネント
 *
 * @param setIsLoggedIn - ログイン状態を更新する関数
 * @param setUser - ログインしたユーザ名を更新する関数
 * @returns JSX.Element
 */
function Login({ setIsLoggedIn, setUser }: LoginProps) {
  const navigate = useNavigate();
  const [name, setName] = useState(""); // ユーザ名を管理
  const [password, setPassword] = useState(""); // パスワードを管理
  const [errorMsg, setErrorMsg] = useState(""); // エラーメッセージを管理

  /* ログイン認証チェック */
  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg("");

    try {
      const res = await axios.post("http://localhost:8000/api/login", { name, password });
      if (res.data.status === "error") {
        setErrorMsg(res.data.message);
        return;
      }

      // トークンをローカルストレージに保存（次回以降のリクエストに使用）
      localStorage.setItem("token", res.data.token);
      setIsLoggedIn(true);
      setUser(res.data.name);
      navigate("/posts");
    } catch (error) {
      console.log("ログインに失敗しました:", error);
      setErrorMsg("ログインに失敗しました。");
    }
  };

  return (
    <div className="login-container">
      <h2>ログイン</h2>
      <form onSubmit={handleLogin} className="login-form">
        <input type="text" value={name} onChange={(e) => setName(e.target.value)} placeholder="名前" />
        <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} placeholder="パスワード" />
        <button type="submit">ログイン</button>
      </form>
      {/* エラーメッセージを表示 */}
      {errorMsg && <p className="login-error-message">{errorMsg}</p>}

      <p>アカウントをお持ちでない方はこちら</p>
      <button className="login-link-button" onClick={() => navigate("/account")}>
        新規登録
      </button>
    </div>
  );
}

export default Login;
