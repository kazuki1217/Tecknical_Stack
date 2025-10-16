export default function NotFound() {
  return (
    <div style={{ textAlign: "center", marginTop: "4rem" }}>
      <h1>404 - ページが見つかりません</h1>
      <p>URLが間違っているか、ページが削除された可能性があります。</p>
      <a href="/" style={{ color: "blue" }}>
        トップページへ戻る
      </a>
    </div>
  );
}
