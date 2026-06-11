// directives/icon.ts
import { Directive, DirectiveBinding } from 'vue';

/**
 * 图标自定义指令
 */
export const icon: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding) {
    const { value } = binding;
    if (!value) return;

    const iElem = document.createElement('i');
    iElem.classList.add('iconfont', value);
    el.insertBefore(iElem, el.firstChild);
  },
  updated(el: HTMLElement, binding: DirectiveBinding) {
    const { value } = binding;
    if (!value) return;

    const iElem = el.querySelector('i');
    if (!iElem) return;

    iElem.className = 'iconfont';
    iElem.classList.add(value);
  },
};