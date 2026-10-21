import select2 from 'select2/dist/js/select2.full';
import 'select2/dist/css/select2.min.css';

try {
  window.select2 = select2;
} catch (e) {}
select2();

export { select2 };
