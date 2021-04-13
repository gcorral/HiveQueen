'''
Created on 8 abr. 2021

@author: user
'''
#import unittest
from django.test import TestCase
import datetime

from hive.timeframe import TimeFrame

class TestTimeFrame(TestCase):

    def setUp(self):
        self.t0 = datetime.datetime.now()
        self.t1 =  self.t0 + datetime.timedelta(days=1)
        self.t2 =  self.t1 + datetime.timedelta(days=1) 
        self.t3 =  self.t2 + datetime.timedelta(days=1)
        self.timeframe0_1 = TimeFrame(self.t0, self.t1)
        self.timeframe1_2 = TimeFrame(self.t1, self.t2)
        self.timeframe2_3 = TimeFrame(self.t2, self.t3)
        self.timeframe0_2 = TimeFrame(self.t0, self.t2)
        self.timeframe1_3 = TimeFrame(self.t1, self.t3)
        self.timeframe0_3 = TimeFrame(self.t0, self.t3)
        
    def testTimeFrame1(self):
        with self.assertRaises(ValueError):
            timeframe = TimeFrame(self.t2, self.t1)
            
    def testTimeFrame2(self):
        with self.assertRaises(Exception):
            timeframe = TimeFrame(1, self.t2)
            
    def testTimeFrame3(self):
        with self.assertRaises(Exception):
            timeframe = TimeFrame(self.t1, 2)
            
    def testTypeSetBegin(self):
        with self.assertRaises(Exception):
            self.timeframe1_2.setBegin(3)
            
    def testValueSetBegin1(self):
        with self.assertRaises(ValueError):
            self.timeframe1_2.setBegin(self.t3)
            
    def testValueSetBegin2(self):
        self.timeframe0_3.setBegin(self.t1)
        self.assertEqual(self.t1, self.timeframe0_3.getBegin())
               
    def testTypeSetEnd(self):
        with self.assertRaises(Exception):
            self.timeframe.setEnd(3)

    def testValueSetEnd1(self):
        with self.assertRaises(ValueError):
            self.timeframe1_2.setEnd(self.t0)
            
    def testValueSetEnd2(self):
        self.timeframe1_2.setEnd(self.t3)
        self.assertEqual(self.t3, self.timeframe1_2.getEnd())

    def testIsOverlapping1(self):
        self.assertEqual(self.timeframe0_2.isOverlapping( self.timeframe1_3 ), True)
        
    def testIsOverlapping2(self):
        self.assertEqual(self.timeframe1_3.isOverlapping( self.timeframe0_2 ), True)
        
    def testIsOverlapping3(self):
        self.assertEqual(self.timeframe0_1.isOverlapping( self.timeframe1_2 ), True)
        
    def testIsOverlapping4(self):
        self.assertEqual(self.timeframe1_2.isOverlapping( self.timeframe0_1 ), True) 
        
    def testIsOverlapping5(self):
        self.assertEqual(self.timeframe0_1.isOverlapping( self.timeframe2_3 ), False) 
        
    def testEqual(self):
        other = TimeFrame(self.t0, self.t2)
        self.assertEqual(other == self.timeframe0_2, True) 
        
    def testUnion1(self):
        union = self.timeframe0_1.union(self.timeframe1_2)
        self.assertEqual(union == self.timeframe0_2, True)
     
    def testUnion2(self):
        union = self.timeframe0_1.union(self.timeframe2_3)
        self.assertEqual(union, self.timeframe0_3)  
        
    def testIntersectionValue(self):
        with self.assertRaises(ValueError):
            other = self.timeframe0_1.intersection( self.timeframe2_3 )
            
    def testIntersection1(self):
        intersection = self.timeframe0_2.intersection( self.timeframe1_3 )
        self.assertEqual(intersection, self.timeframe1_2)
        
    def testIntersection2(self):
        intersection = self.timeframe0_2.intersection( self.timeframe2_3 )
        self.assertEqual(intersection, TimeFrame(self.t2, self.t2 ))
                
    

#if __name__ == "__main__":
    #import sys;sys.argv = ['', 'Test.testName']
    #unittest.main()