export const debounce = (func, wait) => {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      timeout = null;
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};

export const objectOptions = (obj) => {
  if (!obj) {
    return [];
  }

  return Object.entries(obj).map(([key, value]) => ({
    label: value,
    value: key,
  }));
};

export const sortOptions = (options, values = []) => {
  // make sure value is an array.
  values = Array.isArray(values) ? values : [];

  const valueOrder = new Map(values.map((val, index) => [val, index]));

  const sortedOptions = [...options].sort((a, b) => {
    const indexA = valueOrder.has(a.value) ? valueOrder.get(a.value) : Infinity;
    const indexB = valueOrder.has(b.value) ? valueOrder.get(b.value) : Infinity;
    return indexA - indexB;
  });

  return sortedOptions;
};

export const objectOptionsSorted = (obj, value) => {
  if (!value) {
    return objectOptions(obj);
  }

  return sortOptions(objectOptions(obj), value);
};
