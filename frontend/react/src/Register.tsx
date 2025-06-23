import { useState } from 'react';
import { useNavigate } from 'react-router-dom';

// アカウント登録画面
function Register() {
  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
  });
  const [message, setMessage] = useState('');
  const [success, setSuccess] = useState(false);
  const navigate = useNavigate();

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    // アカウント登録処理
    const res = await fetch('http://localhost:8000/api/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(form)
    });

    const data = await res.json();
    console.log('ステータス:', res.status);
    console.log('結果:', data);

    if (res.ok) {
      setMessage('登録が完了しました。');
      setSuccess(true);
    } else {
      setMessage(data.message || '登録に失敗しました。');
      setSuccess(false);
    }
  };

  return (
    <div>
      <h2>ユーザ登録</h2>
      <form onSubmit={handleSubmit}>
        <input name="name" placeholder="名前" onChange={handleChange} /><br />
        <input name="email" type="email" placeholder="メールアドレス" onChange={handleChange} /><br />
        <input name="password" type="password" placeholder="パスワード" onChange={handleChange} /><br />
        <input name="password_confirmation" type="password" placeholder="パスワード確認" onChange={handleChange} /><br />
        <button type="submit">登録</button>
      </form>
      <p>{message}</p>
      {success && (
        <p>
          <a href="/" onClick={(e) => {
            e.preventDefault();
            navigate('/');
          }}>ログイン画面はこちら</a>
        </p>
      )}
    </div>
  );
}

export default Register;
