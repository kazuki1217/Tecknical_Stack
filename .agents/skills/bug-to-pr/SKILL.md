---
name: bug-to-pr
description: 不具合調査からIssue作成、実装、Draft PR作成までまとめて進行する。
---

# 不具合からDraft PRまで進める

## 進め方

1. `../bug-investigate/SKILL.md`に従って調査する。
2. 調査結果を報告し、コードを変更せずに停止する。
3. 利用者から「Issue作成からDraft PRまで進める」と明確に承認された場合だけ再開する。
4. `../issue-create/SKILL.md`に従ってIssue草案を作り、利用者の承認を得てからIssueを作成する。
5. Issue作成後、`../issue-implement/SKILL.md`に従って実装と検証を行う。
6. `../pr-create/SKILL.md`に従ってDraft PRを作成する。
7. Issue、PR、テスト結果をまとめて返す。

## ルール

- 各Skillの停止条件に該当したら、自動処理を止めて理由を示す。
- PRのマージは行わない。最終判断は利用者に任せる。
