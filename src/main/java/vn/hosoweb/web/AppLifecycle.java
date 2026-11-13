package vn.hosoweb.web;

import jakarta.servlet.ServletContextEvent;
import jakarta.servlet.ServletContextListener;
import vn.hosoweb.util.HibernateUtil;

public class AppLifecycle implements ServletContextListener {
    @Override public void contextInitialized(ServletContextEvent sce) { HibernateUtil.factory(); }
    @Override public void contextDestroyed(ServletContextEvent sce) { HibernateUtil.shutdown(); }
}
