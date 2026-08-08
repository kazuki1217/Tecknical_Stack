import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import axios, { AxiosError } from 'axios'
import { FaEye, FaEyeSlash } from 'react-icons/fa'

import { api } from '../lib/api'
import '../styles/Login.css'

interface LoginProps {
  setIsLoggedIn: React.Dispatch<React.SetStateAction<boolean | null>>
  setLoggedInUserId: React.Dispatch<React.SetStateAction<number | null>>
  setLoggedInUserName: React.Dispatch<React.SetStateAction<string | null>>
}

/**
 * ログイン画面コンポーネント
 *
 * @param setIsLoggedIn - ログイン状態を更新する関数
 * @param setLoggedInUserId - ログインしたユーザーIDを更新する関数
 * @param setLoggedInUserName - ログインしたユーザ名を更新する関数
 * @returns JSX.Element
 */
function Login({
  setIsLoggedIn,
  setLoggedInUserId,
  setLoggedInUserName,
}: LoginProps) {
  const navigate = useNavigate()
  const [email, setEmail] = useState('') // メールアドレスを管理
  const [password, setPassword] = useState('') // パスワードを管理
  const [isPasswordVisible, setIsPasswordVisible] = useState(false) // パスワードの表示状態を管理
  const [errorMsg, setErrorMsg] = useState('') // エラーメッセージを管理
  const [isSubmitting, setIsSubmitting] = useState(false) // 送信中か否かを管理（連打による重複リクエストを防ぐため）

  /* ログイン認証チェック */
  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault()
    setErrorMsg('')
    setIsSubmitting(true)

    try {
      const res = await api.post('/api/login', { email, password })

      localStorage.setItem('token', res.data.data.token) // トークンをローカルストレージに保存（次回以降のリクエストに使用）
      setIsLoggedIn(true)
      setLoggedInUserId(res.data.data.id)
      setLoggedInUserName(res.data.data.name)
      navigate('/posts')
    } catch (error) {
      if (axios.isAxiosError(error) && error.response?.status === 429) {
        const response = error.response as AxiosError<{
          message: string
        }>['response']
        setErrorMsg(response?.data.message ?? 'ログインに失敗しました。')
      } else {
        console.log('ログインに失敗しました:', error)
        setErrorMsg('ログインに失敗しました。')
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="login-container">
      <h2>ログイン</h2>
      <p className="login-sample-account">
        デモ用アカウント：user1@example.com / user1pass
      </p>
      <form onSubmit={handleLogin} className="login-form">
        <input
          type="text"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="メールアドレス"
        />
        <div className="login-password-field">
          <input
            type={isPasswordVisible ? 'text' : 'password'}
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="パスワード"
          />
          <button
            type="button"
            className="login-password-toggle"
            onClick={() => setIsPasswordVisible((isVisible) => !isVisible)}
            aria-label={
              isPasswordVisible ? 'パスワードを非表示' : 'パスワードを表示'
            }
            aria-pressed={isPasswordVisible}
          >
            {isPasswordVisible ? <FaEye /> : <FaEyeSlash />}
          </button>
        </div>
        <button
          type="submit"
          className="login-submit-button"
          disabled={isSubmitting}
        >
          ログイン
        </button>
      </form>
      {/* エラーメッセージを表示 */}
      {errorMsg && <p className="login-error-message">{errorMsg}</p>}

      <p>アカウントをお持ちでない方はこちら</p>
      <button
        className="login-link-button"
        onClick={() => navigate('/account')}
      >
        新規登録
      </button>
    </div>
  )
}

export default Login
