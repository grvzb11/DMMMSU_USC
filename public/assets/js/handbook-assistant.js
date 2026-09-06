/**
 * Developed by: George Rexy Vincent Z. Bacani
 * College: College of Information Technology
 * Role: System Developer / Front-End Developer
 * Development Year: 2026–2027
 * Institution: Don Mariano Marcos Memorial State University
 * Version: v1.0
 * Email: rexygeorge11@gmail.com
 * Copyright: © 2026–2027. All rights reserved.
 */
(() => {
  const root = document.getElementById('handbookAi');
  if (!root) return;
  const launcher = root.querySelector('[data-handbook-launcher]');
  const panel = root.querySelector('[data-handbook-panel]');
  const closeBtn = root.querySelector('[data-handbook-close]');
  const form = root.querySelector('[data-handbook-form]');
  const input = root.querySelector('[data-handbook-input]');
  const send = root.querySelector('[data-handbook-send]');
  const messages = root.querySelector('[data-handbook-messages]');
  const body = root.querySelector('.handbook-ai__body');
  const ready = root.dataset.ready === '1';
  const endpoint = root.dataset.endpoint;
  const sourceBase = root.dataset.source;
  const esumbongUrl = root.dataset.esumbong;
  const history = [];
  let busy = false;

  const setOpen = open => {
    root.classList.toggle('is-open', open);
    launcher?.setAttribute('aria-expanded', open ? 'true' : 'false');
    panel?.setAttribute('aria-hidden', open ? 'false' : 'true');
    if (open) setTimeout(() => input?.focus(), 100);
  };
  launcher?.addEventListener('click', () => setOpen(!root.classList.contains('is-open')));
  closeBtn?.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && root.classList.contains('is-open')) setOpen(false); });

  const addBubble = (role, text) => {
    const wrap = document.createElement('div');
    wrap.className = `handbook-ai__message handbook-ai__message--${role}`;
    const bubble = document.createElement('div');
    bubble.className = 'handbook-ai__bubble';
    bubble.textContent = text;
    wrap.appendChild(bubble);
    messages.appendChild(wrap);
    body.scrollTop = body.scrollHeight;
    return wrap;
  };

  const addTyping = () => {
    const wrap = document.createElement('div');
    wrap.className = 'handbook-ai__message handbook-ai__message--assistant';
    wrap.dataset.typing = '1';
    wrap.innerHTML = '<div class="handbook-ai__typing" aria-label="Searching handbook"><i></i><i></i><i></i></div>';
    messages.appendChild(wrap);
    body.scrollTop = body.scrollHeight;
    return wrap;
  };

  const addSources = (wrap, data) => {
    if (!data || data.not_found) return;
    const source = document.createElement('div');
    source.className = 'handbook-ai__source';
    const label = document.createElement('span');
    label.className = 'handbook-ai__source-label';
    label.textContent = 'Handbook source';
    source.appendChild(label);
    if (Array.isArray(data.pages) && data.pages.length) {
      const links = document.createElement('div');
      links.className = 'handbook-ai__source-links';
      data.pages.forEach(page => {
        const a = document.createElement('a');
        a.className = 'handbook-ai__source-link';
        a.href = `${sourceBase}#page=${encodeURIComponent(page)}`;
        a.target = '_blank';
        a.rel = 'noopener';
        a.textContent = `📖 Page ${page}`;
        links.appendChild(a);
      });
      source.appendChild(links);
    }
    const doc = document.createElement('span');
    doc.className = 'handbook-ai__source-doc';
    doc.textContent = data.title || 'DMMMSU Student Handbook';
    source.appendChild(doc);
    wrap.appendChild(source);

    const follow = document.createElement('div');
    follow.className = 'handbook-ai__followup';
    const feedback = document.createElement('div');
    feedback.className = 'handbook-ai__feedback';
    feedback.innerHTML = '<span>Helpful?</span><button type="button" aria-label="Helpful">👍</button><button type="button" aria-label="Not helpful">👎</button>';
    feedback.querySelectorAll('button').forEach(btn => btn.addEventListener('click', () => {
      feedback.innerHTML = '<span>Thanks for your feedback.</span>';
    }));
    const concern = document.createElement('a');
    concern.className = 'handbook-ai__esumbong';
    concern.href = esumbongUrl;
    concern.textContent = 'Need help? E-Sumbong →';
    follow.append(feedback, concern);
    wrap.appendChild(follow);
  };

  const ask = async question => {
    if (busy || !question) return;
    if (!ready) {
      addBubble('assistant', 'The Handbook Assistant is being configured by the System Administrator. Please try again later.');
      return;
    }
    busy = true;
    send.disabled = true;
    addBubble('user', question);
    const priorHistory = history.slice(-6);
    history.push({role:'user', text:question});
    const typing = addTyping();
    try {
      const bodyData = new URLSearchParams({question, history: JSON.stringify(priorHistory)});
      const response = await fetch(endpoint, {
        method:'POST',
        headers:{'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','X-Requested-With':'XMLHttpRequest'},
        credentials:'same-origin',
        body:bodyData
      });
      const data = await response.json().catch(() => ({}));
      typing.remove();
      if (!response.ok) throw new Error(data.error || 'The Handbook Assistant could not answer right now.');
      const wrap = addBubble('assistant', data.answer || 'I could not find enough information in the Student Handbook.');
      addSources(wrap, data);
      history.push({role:'assistant', text:(data.answer || '').slice(0,700)});
    } catch (error) {
      typing.remove();
      const wrap = addBubble('assistant', error.message || 'The Handbook Assistant could not answer right now.');
      wrap.querySelector('.handbook-ai__bubble')?.classList.add('handbook-ai__error');
    } finally {
      busy = false;
      send.disabled = false;
      body.scrollTop = body.scrollHeight;
    }
  };

  form?.addEventListener('submit', e => {
    e.preventDefault();
    const question = (input.value || '').trim();
    if (!question) return;
    input.value = '';
    input.style.height = '';
    ask(question);
  });
  input?.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form?.requestSubmit(); }
  });
  input?.addEventListener('input', () => {
    input.style.height = 'auto';
    input.style.height = `${Math.min(94, input.scrollHeight)}px`;
  });
  root.querySelectorAll('[data-handbook-question]').forEach(button => {
    button.addEventListener('click', () => {
      setOpen(true);
      input.value = button.dataset.handbookQuestion || button.textContent.trim();
      form?.requestSubmit();
    });
  });
})();
