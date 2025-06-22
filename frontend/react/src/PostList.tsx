// ログイン認証後の画面
function PostList({ user }: { user: string }) {
  return <h1>こんにちは「{user}」さん</h1>;
}

export default PostList;
