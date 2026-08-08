import { useRef, useState } from 'react'

import { buildApiErrorMessage } from '../utils/apiErrorMessage'

/** 書き込み処理の実行状態（未実行 / 実行中 / 失敗） */
type ActionStatus = 'idle' | 'running' | 'error'

/**
 * 書き込み処理（投稿・コメントの作成、更新、削除など）の実行状態を管理するフック
 *
 * 送信中はボタンを無効化して二重送信を防ぎ、失敗時は画面に表示する文言を保持する。
 * 1つのフックが管理するのは1種類の処理のため、同じ画面に複数の書き込み処理がある場合は処理ごとに呼び出す。
 *
 * @param fallbackErrorMessage - 失敗時に表示する既定の文言（例: '投稿の作成に失敗しました。'）
 * @returns isRunning（実行中か）、errorMessage（失敗時の文言。未実行・成功時は null）、run（処理を実行する関数）
 */
export function useAsyncAction(fallbackErrorMessage: string) {
  const [status, setStatus] = useState<ActionStatus>('idle')
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const isRunningRef = useRef(false) // 再描画でボタンが無効化されるより前の連打を弾くため、stateとは別に即時反映される値でも実行中を判定する

  /**
   * 渡した処理を実行し、実行状態と失敗時の文言を更新する
   *
   * @param action - 実行する非同期処理
   * @returns 成功した場合は true、失敗した場合と実行中に呼ばれた場合は false（入力欄の初期化など、成功時だけ行いたい後処理の判定に使う）
   */
  const run = async (action: () => Promise<void>): Promise<boolean> => {
    if (isRunningRef.current) {
      return false
    }
    isRunningRef.current = true

    setStatus('running')
    setErrorMessage(null)

    try {
      await action()
      setStatus('idle')
      return true
    } catch (error) {
      console.error(`${fallbackErrorMessage}:`, error)
      setErrorMessage(buildApiErrorMessage(error, fallbackErrorMessage))
      setStatus('error')
      return false
    } finally {
      isRunningRef.current = false
    }
  }

  return { isRunning: status === 'running', errorMessage, run }
}
