// ログイン認証後の画面

import { useNavigate } from 'react-router-dom';

function PostList({ user }: { user: string }) {
  const navigate = useNavigate();

  // ログアウト処理
  const handleLogout = () => {
    localStorage.removeItem('token');
    navigate('/');
    window.location.reload(); // App.tsx を再評価させて状態をリセット
  };

  return (
    <div>
      <h1>こんにちは「{user}」さん</h1>
      <button onClick={handleLogout}>ログアウト</button>
    </div>
  );}

export default PostList;
