package vn.hosoweb.service;

import java.util.List;

public final class Csv {
    private Csv() { }

    public static String row(List<?> cells) {
        StringBuilder sb = new StringBuilder();
        for (int i = 0; i < cells.size(); i++) {
            if (i > 0) sb.append(',');
            sb.append(cell(cells.get(i)));
        }
        return sb.append("\r\n").toString();
    }

    static String cell(Object o) {
        String s = o == null ? "" : o.toString();
        // Chặn công thức (CSV injection) khi mở bằng Excel / Google Sheets.
        if (!s.isEmpty() && "=+-@\t\r".indexOf(s.charAt(0)) >= 0) s = "'" + s;
        if (s.contains(",") || s.contains("\"") || s.contains("\n") || s.contains("\r")) {
            s = "\"" + s.replace("\"", "\"\"") + "\"";
        }
        return s;
    }
}
