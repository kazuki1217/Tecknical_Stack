import { useState } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";

/** 新規登録画面を構成 */
function Register() {
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
  });
  const [message, setMessage] = useState("");
  const [success, setSuccess] = useState(false);
  const navigate = useNavigate();

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      // アカウント登録処理
      const res = await axios.post("http://localhost:8000/api/register", form);
      console.log("ステータス:", res.status);
      setMessage("登録が完了しました。");
      setSuccess(true);
    } catch (error) {
      console.log(error);
      setMessage("登録に失敗しました。");
      setSuccess(false);
    }
  };

  return (
    <div className="auth-container">
      <h2>新規登録</h2>
      <form onSubmit={handleSubmit} className="auth-form">
        <input name="name" placeholder="名前" onChange={handleChange} />
        <input name="email" type="email" placeholder="メールアドレス" onChange={handleChange} />
        <input name="password" type="password" placeholder="パスワード" onChange={handleChange} />
        <input name="password_confirmation" type="password" placeholder="パスワード確認" onChange={handleChange} />
        <button type="submit">登録</button>
      </form>
      {message && <p className={success ? "success-message" : "error-message"}>{message}</p>}
      {success && (
        <button className="link-button" onClick={() => navigate("/")}>
          ログイン画面はこちら
        </button>
      )}
    </div>
  );
}

export default Register;
