package vn.hosoweb.service;

import org.junit.jupiter.api.Test;

import java.util.Arrays;

import static org.junit.jupiter.api.Assertions.*;

class CsvAndSinbyteTest {
    @Test
    void csvEscapesAndBlocksFormulas() {
        assertEquals("a,\"b,c\",\"q\"\"x\",'=SUM(A1),\r\n", Csv.row(Arrays.asList("a", "b,c", "q\"x", "=SUM(A1)", null)));
    }

    @Test
    void sinbyteInterpretsResponses() {
        var ok = SinbyteClient.interpret(200, "{\"status\":\"ok\",\"task_id\":\"T123\"}");
        assertTrue(ok.accepted());
        assertEquals("T123", ok.taskId());

        var nested = SinbyteClient.interpret(200, "{\"data\":{\"id\":42}}");
        assertTrue(nested.accepted());
        assertEquals("42", nested.taskId());

        var err = SinbyteClient.interpret(200, "{\"status\":\"error\",\"message\":\"Hết credit\"}");
        assertFalse(err.accepted());
        assertEquals("Hết credit", err.message());

        assertFalse(SinbyteClient.interpret(401, "Unauthorized").accepted());
    }
}
