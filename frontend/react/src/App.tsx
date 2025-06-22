// frontend/react/src/App.tsx

import { useEffect, useState } from 'react';

function App() {
  const [msg, setMsg] = useState('');

  useEffect(() => {
    fetch('http://localhost:8000/api/message')
      .then(res => res.json())
      .then(data => setMsg(data.message))
      .catch(console.error);
  }, []);

  return <h1>{msg}</h1>;
}

export default App;
