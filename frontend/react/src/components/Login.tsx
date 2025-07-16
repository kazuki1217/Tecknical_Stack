import { useState } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";

interface LoginProps {
  setIsLoggedIn: React.Dispatch<React.SetStateAction<boolean>>;
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
  const [name, setName] = useState("");
  const [password, setPassword] = useState("");
  const [errorMsg, setErrorMsg] = useState("");
  const navigate = useNavigate();

  // フォームの送信時の処理（ログイン）
  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg("");

    // ログイン情報を送信
    try {
      const res = await axios.post("http://localhost:8000/api/login", {
        name,
        password,
      });

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
      console.log(error);
      setErrorMsg("ログインに失敗しました。");
    }
  };

  return (
    <div className="auth-container">
      <h2>ログイン</h2>
      <form onSubmit={handleLogin} className="auth-form">
        <input type="text" value={name} onChange={(e) => setName(e.target.value)} placeholder="名前" />
        <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} placeholder="パスワード" />
        <button type="submit">ログイン</button>
      </form>
      {/* エラーメッセージを表示 */}
      {errorMsg && <p className="error-message">{errorMsg}</p>}

      <p>アカウントをお持ちでない方はこちら</p>
      <button className="link-button" onClick={() => navigate("/account")}>
        新規登録
      </button>
    </div>
  );
}

export default Login;
