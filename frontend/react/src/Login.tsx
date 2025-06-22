// ログイン画面
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';

// propsとしてsetUser関数を受け取る（ログイン成功時に親コンポーネントにユーザー名を渡すため）
function Login({ setUser }: { setUser: (name: string) => void }) {
  // 入力されたユーザー名とパスワードを保持するstate
  const [name, setName] = useState('');
  const [password, setPassword] = useState('');
  const navigate = useNavigate();

  // フォームの送信時の処理（ログイン）
  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();

    // ログイン情報を送信
    const res = await fetch('http://localhost:8000/api/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, password }),
    });

    if (res.ok) {
      // トークンを取得
      const data = await res.json();
      const token = data.token;
      
      // トークンをローカルストレージに保存（次回以降のリクエストに使用）
      localStorage.setItem('token', token);

      // トークンを使ってユーザー情報を取得
      const userRes = await fetch('http://localhost:8000/api/loginsuccess', {
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });
        
      // ユーザー名を取り出して状態更新
      const user = await userRes.json();
      setUser(user.name); // 親コンポーネントにユーザー名を通知
      // 投稿一覧画面に遷移
      navigate('/posts');

    } else {
      alert('ログイン失敗');
    }
  };

  return (
    <div>
      <h2>ログイン</h2>
      <form onSubmit={handleLogin}>
        <input type="name" value={name} onChange={e => setName(e.target.value)} placeholder="名前" /><br />
        <input type="password" value={password} onChange={e => setPassword(e.target.value)} placeholder="パスワード" /><br />
        <button type="submit">ログイン</button>
      </form>
      <p>アカウントをお持ちでない方はこちら</p>
      <button onClick={() => navigate('/account')}>新規登録</button>
    </div>
  );
}

export default Login;
