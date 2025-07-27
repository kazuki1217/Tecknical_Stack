import { useState } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";

import "../styles/Register.css";

/**
 * 新規登録画面コンポーネント
 *
 * @returns JSX.Element
 */
function Register() {
  const [message, setMessage] = useState(""); // 登録時の成功メッセージ、失敗メッセージの管理
  const [success, setSuccess] = useState(false); // 登録の成功状態を管理
  const [form, setForm] = useState({ name: "", email: "", password: "", password_confirmation: "" }); // 入力フォームの情報を管理
  const navigate = useNavigate();

  /* 入力フォームの状態を管理 */
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  /* 新規アカウントの登録処理 */
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      const res = await axios.post("http://localhost:8080/api/register", form);
      console.log("ステータス:", res.status);
      setMessage("登録が完了しました。");
      setSuccess(true);
    } catch (error) {
      console.log("登録に失敗しました:", error);
      setMessage("登録に失敗しました。");
      setSuccess(false);
    }
  };

  return (
    <div className="register-container">
      <h2>新規登録</h2>
      <form onSubmit={handleSubmit} className="register-form">
        <input name="name" placeholder="名前" onChange={handleChange} />
        <input name="email" type="email" placeholder="メールアドレス" onChange={handleChange} />
        <input name="password" type="password" placeholder="パスワード" onChange={handleChange} />
        <input name="password_confirmation" type="password" placeholder="パスワード確認" onChange={handleChange} />
        <button type="submit">登録</button>
      </form>
      {/* アカウント登録に失敗したか否かを表示 */}
      {message && <p className={success ? "register-success-message" : "register-error-message"}>{message}</p>}
      {success && (
        <button className="register-link-button" onClick={() => navigate("/")}>
          ログイン画面はこちら
        </button>
      )}
    </div>
  );
}

export default Register;
