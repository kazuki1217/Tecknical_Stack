import { useState } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";

// アカウント登録画面
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
    <div>
      <h2>ユーザ登録</h2>
      <form onSubmit={handleSubmit}>
        <input name="name" placeholder="名前" onChange={handleChange} />
        <br />
        <input name="email" type="email" placeholder="メールアドレス" onChange={handleChange} />
        <br />
        <input name="password" type="password" placeholder="パスワード" onChange={handleChange} />
        <br />
        <input name="password_confirmation" type="password" placeholder="パスワード確認" onChange={handleChange} />
        <br />
        <button type="submit">登録</button>
      </form>
      <p>{message}</p>
      {success && (
        <p>
          <a
            href="/"
            onClick={(e) => {
              e.preventDefault();
              navigate("/");
            }}
          >
            ログイン画面はこちら
          </a>
        </p>
      )}
    </div>
  );
}

export default Register;
